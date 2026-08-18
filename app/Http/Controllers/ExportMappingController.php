<?php

namespace App\Http\Controllers;

use App\Models\ExportTemplate;
use App\Services\ExportMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola template export dinamis (tabel `export_templates` +
 * `export_template_mappings`).
 *
 * Halaman:
 *  - index  : daftar template (Edit / Hapus) + tombol "Template Baru"
 *  - create : buat template baru (upload CSV → cocokkan kolom → simpan)
 *  - edit   : ubah template yang sudah ada (nama/couriers/mapping)
 *
 * Template custom (mis. JNE) langsung muncul sebagai opsi export di halaman
 * Data Mentah (OrderOnlineController). Hapus = permanen (mapping ikut
 * terhapus); rule courier fallback (`spx`) tetap jadi safety net.
 */
class ExportMappingController extends Controller
{
    public function __construct(
        private readonly ExportMappingService $mappings,
    ) {}

    public function index(): View
    {
        $templates = ExportTemplate::query()
            ->withCount([
                'mappings as columns_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('id')
            ->get();

        return view('export_mapping.index', [
            'templates' => $templates,
            'columns' => ExportMappingService::COLUMNS,
            'computed' => ExportMappingService::COMPUTED,
        ]);
    }

    public function create(): View
    {
        return view('export_mapping.form', [
            'template' => null,
            'mapping' => collect(),
            'columns' => ExportMappingService::COLUMNS,
            'computed' => ExportMappingService::COMPUTED,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTemplate($request);

        $this->mappings->createTemplate(
            $data['name'],
            $this->parseCouriers($data['couriers'] ?? ''),
            $this->parseItems($data['items']),
        );

        return redirect()->route('export-mapping.index')->with('success', 'Template baru berhasil dibuat.');
    }

    public function edit(ExportTemplate $exportTemplate): View
    {
        return view('export_mapping.form', [
            'template' => $exportTemplate,
            'mapping' => $this->mappings->mappingFor($exportTemplate->key),
            'columns' => ExportMappingService::COLUMNS,
            'computed' => ExportMappingService::COMPUTED,
        ]);
    }

    public function update(Request $request, ExportTemplate $exportTemplate): RedirectResponse
    {
        $data = $this->validateTemplate($request);

        $this->mappings->updateTemplate(
            $exportTemplate,
            $data['name'],
            $this->parseCouriers($data['couriers'] ?? ''),
            $this->parseItems($data['items']),
        );

        return redirect()->route('export-mapping.index')->with('success', 'Template '.$exportTemplate->name.' berhasil diperbarui.');
    }

    public function destroy(ExportTemplate $exportTemplate): RedirectResponse
    {
        $name = $exportTemplate->name;
        $this->mappings->deleteTemplate($exportTemplate);

        return redirect()->route('export-mapping.index')->with('success', "Template {$name} dihapus permanen (termasuk mapping-nya).");
    }

    /**
     * Parse header dari template CSV yang di-upload (tanpa menyimpan).
     * `template` opsional — diisi saat edit agar mapping lama ikut terbawa
     * (cocok berdasarkan nama header); saat create kosong → semua sumber empty.
     */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv', 'max:2048'],
            'template' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $headers = $this->mappings->parseTemplateFile($request->file('file')->getPathname());
            $templateKey = $data['template'] ?? '';
            $matched = $templateKey !== '' && $this->mappings->template($templateKey) !== null
                ? $this->mappings->matchHeaders($templateKey, $headers)
                : array_map(fn ($h) => ['header' => $h, 'source_type' => 'empty', 'source_value' => null], $headers);

            return response()->json(['success' => true, 'headers' => $matched]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca template: '.$e->getMessage(),
            ], 422);
        }
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'couriers' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.column_index' => ['required', 'integer', 'min:0'],
            'items.*.header' => ['required', 'string', 'max:255'],
            'items.*.source_type' => ['required', 'in:'.implode(',', ExportMappingService::SOURCE_TYPES)],
            'items.*.source_value' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseCouriers(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        return $parts;
    }

    /**
     * Validasi sumber per kolom + rapi kan struktur items.
     *
     * @return array<int, array{column_index:int, header:string, source_type:string, source_value:string}>
     */
    private function parseItems(array $items): array
    {
        $indexes = array_column($items, 'column_index');
        if (count($indexes) !== count(array_unique($indexes))) {
            abort(422, 'Ada nomor kolom yang dobel. Setiap kolom hanya boleh diisi sekali.');
        }

        $result = [];
        foreach ($items as $item) {
            $item['source_value'] = trim((string) ($item['source_value'] ?? ''));

            if ($item['source_type'] === 'column' && ! isset(ExportMappingService::COLUMNS[$item['source_value']])) {
                abort(422, 'Ada kolom sumber yang tidak valid (kolom shipping_orders tidak dikenal).');
            }
            if ($item['source_type'] === 'computed' && ! isset(ExportMappingService::COMPUTED[$item['source_value']])) {
                abort(422, 'Ada nilai khusus yang tidak valid (key computed tidak dikenal).');
            }
            if ($item['source_type'] === 'static' && $item['source_value'] === '') {
                abort(422, 'Sumber "teks tetap" tidak boleh kosong.');
            }

            $result[] = [
                'column_index' => (int) $item['column_index'],
                'header' => trim((string) $item['header']),
                'source_type' => $item['source_type'],
                'source_value' => $item['source_value'],
            ];
        }

        return $result;
    }
}
