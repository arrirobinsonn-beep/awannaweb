<?php

namespace App\Http\Controllers;

use App\Models\ExportTemplate;
use App\Models\ShippingOrder;
use App\Models\TrackingHeaderMapping;
use App\Models\TrackingSourceConfig;
use App\Models\TrackingStatusRule;
use App\Services\AggregatorTrackingImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Kelola aturan dinamis raw status dashboard aggregator → aggregator_status
 * (tabel `tracking_status_rules`), pengganti map hardcoded di service import.
 *
 * Rules dievaluasi per sumber berurutan dari `sort_order` terkecil; rule
 * pertama yang cocok menang. Rule dengan problem_mode='required' hanya cocok
 * bila kolom masalah file terpenuhi (FLIK 3PL / SPX OnHold reason).
 *
 * Konvensi nilai:
 *  - raw_status disimpan lowercase (dicocokkan ke status file yang di-lowercase)
 *  - problem_keyword null/'' = kolom masalah cukup tidak kosong
 *  - status hanya boleh salah satu dari ShippingOrder::TRACKING_STATUSES
 */
class TrackingStatusRuleController extends Controller
{
    public function __construct(
        private readonly AggregatorTrackingImportService $importer = new AggregatorTrackingImportService,
    ) {}

    /**
     * Halaman daftar dashboard (FLIK / SiCepat / SPX) — satu kartu per sumber,
     * berisi jumlah mapping header & jumlah aturan status, tombol Edit ke
     * halaman per dashboard (pola export template).
     */
    public function index(): View
    {
        // SOURCES dinamis dari export_templates + SOURCES lama (backward-compat)
        $sources = $this->validSources();
        $exportTemplates = ExportTemplate::where('is_active', true)->get();
        // templateMap: key = template key (flik/sicepat/spx/idxeveropro), value = ExportTemplate
        $templateMap = $exportTemplates->keyBy('key')->all();

        $headerCounts = TrackingHeaderMapping::query()
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->all();
        $ruleCounts = TrackingStatusRule::query()
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->all();

        return view('tracking_status_rule.index', [
            'sources' => $sources,
            'headerCounts' => $headerCounts,
            'ruleCounts' => $ruleCounts,
            'exportTemplates' => $exportTemplates,
            'templateMap' => $templateMap,
        ]);
    }

    /**
     * Halaman per dashboard: mapping KOLOM DATABASE → header CSV (pola export
     * template, tapi UI dibalik — kiri kolom DB teks statis, kanan pilih header)
     * + aturan status (raw → sistem) khusus sumber ini.
     */
    public function edit(string $source): View
    {
        $source = strtolower(trim($source));
        if (! in_array($source, $this->validSources(), true)) {
            abort(404);
        }

        $rules = TrackingStatusRule::where('source', $source)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Mapping lama: db_column => header (untuk pre-select di UI yang dibalik)
        $mapping = TrackingHeaderMapping::where('source', $source)
            ->get(['header', 'db_column'])
            ->pluck('header', 'db_column')
            ->all();

        return view('tracking_status_rule.edit', [
            'source' => $source,
            'mapping' => $mapping,
            'columns' => TrackingHeaderMapping::COLUMNS,
            'phoneFormat' => TrackingSourceConfig::where('source', $source)->value('phone_format') ?? 'auto',
            'phoneFormats' => TrackingSourceConfig::PHONE_FORMATS,
            'rules' => $rules,
            'matchTypes' => TrackingStatusRule::MATCH_TYPES,
            'problemModes' => TrackingStatusRule::PROBLEM_MODES,
            'problemMatchTypes' => TrackingStatusRule::PROBLEM_MATCH_TYPES,
            'statuses' => ShippingOrder::TRACKING_STATUSES,
            'nextOrder' => ($rules->max('sort_order') ?? 0) + 1,
        ]);
    }

    /**
     * Simpan konfigurasi per dashboard (format No HP di file).
     */
    public function saveConfig(Request $request, string $source): RedirectResponse
    {
        $source = strtolower(trim($source));
        if (! in_array($source, $this->validSources(), true)) {
            abort(404);
        }

        $data = $request->validate([
            'phone_format' => ['required', 'string', 'in:'.implode(',', TrackingSourceConfig::PHONE_FORMATS)],
        ]);

        TrackingSourceConfig::updateOrCreate(
            ['source' => $source],
            ['phone_format' => $data['phone_format']],
        );

        return redirect()->route('tracking-status-rule.edit', $source)
            ->with('success', 'Format No HP '.strtoupper($source).' disimpan.');
    }

    /**
     * Baca file dashboard (upload dari halaman per-dashboard) dan ekstrak
     * header CSV unik — pola upload template export. Mapping lama ikut terbawa
     * per kolom database (db_column => header) agar pre-select di UI.
     */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'source' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            // Simpan dulu agar path punya ekstensi (temp upload tanpa ekstensi)
            $path = $request->file('file')->store('order-online/tracking');
            $result = $this->importer->extractHeaders(Storage::path($path), $data['source'] ?? null);

            return response()->json([
                'success' => true,
                'source' => $result['source'],
                'headers' => $result['headers'],
                'mapping' => $result['mapping'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Simpan mapping header CSV → kolom database untuk satu dashboard (pola
     * saveMapping export template). Bulk replace per sumber dalam 1 transaksi;
     * satu header tidak boleh dipakai dua kolom database.
     */
    public function saveMapping(Request $request, string $source): RedirectResponse
    {
        $source = strtolower(trim($source));
        if (! in_array($source, $this->validSources(), true)) {
            abort(404);
        }

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.db_column' => ['required', 'string', 'max:50', 'in:'.implode(',', array_keys(TrackingHeaderMapping::COLUMNS))],
            'items.*.header' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $count = $this->importer->saveHeaderMapping($source, $data['items']);
        } catch (\RuntimeException $e) {
            return redirect()->route('tracking-status-rule.edit', $source)
                ->withErrors(['mapping' => $e->getMessage()]);
        }

        return redirect()->route('tracking-status-rule.edit', $source)
            ->with('success', "Mapping header {$source} berhasil disimpan ({$count} kolom).");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->normalize($request->validate($this->rules()));

        if ($this->duplicateExists($data)) {
            return back()->withErrors([
                'rule' => 'Aturan dengan kombinasi sumber + raw status + jenis ini sudah ada. Ubah/hapus aturan lama dulu.',
            ]);
        }

        TrackingStatusRule::create($data);

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil ditambahkan.');
    }

    public function update(Request $request, TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $data = $this->normalize($request->validate($this->rules()));

        if ($this->duplicateExists($data, $trackingStatusRule->id)) {
            return back()->withErrors([
                'rule' => 'Aturan dengan kombinasi sumber + raw status + jenis ini sudah ada. Ubah/hapus aturan lama dulu.',
            ]);
        }

        $trackingStatusRule->update($data);

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil diperbarui.');
    }

    public function destroy(TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $trackingStatusRule->delete();

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil dihapus.');
    }

    public function toggle(TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $trackingStatusRule->update(['is_active' => ! $trackingStatusRule->is_active]);

        return back()->with('success', $trackingStatusRule->is_active
            ? 'Aturan status diaktifkan.'
            : 'Aturan status dinonaktifkan.');
    }

    /** Naik/turunkan prioritas (sort_order) dengan menukar rule tetangga. */
    public function move(TrackingStatusRule $trackingStatusRule, string $direction): RedirectResponse
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            abort(404);
        }

        $rules = TrackingStatusRule::orderBy('source')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $index = $rules->search(fn ($r) => $r->id === $trackingStatusRule->id);
        $swap = $index === false
            ? null
            : ($direction === 'up' ? $rules->get($index - 1) : $rules->get($index + 1));

        if ($swap) {
            DB::transaction(function () use ($trackingStatusRule, $swap) {
                $tmp = $trackingStatusRule->sort_order;
                $trackingStatusRule->update(['sort_order' => $swap->sort_order]);
                $swap->update(['sort_order' => $tmp]);
            });
        }

        return back();
    }

    private function rules(): array
    {
        return [
            'source' => ['required', 'string', 'in:'.implode(',', $this->validSources())],
            'raw_status' => ['required', 'string', 'max:191'],
            'match_type' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::MATCH_TYPES)],
            'status' => ['required', 'string', 'in:'.implode(',', ShippingOrder::TRACKING_STATUSES)],
            'problem_mode' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::PROBLEM_MODES)],
            'problem_keyword' => ['nullable', 'string', 'max:191'],
            'problem_match_type' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::PROBLEM_MATCH_TYPES)],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function normalize(array $data): array
    {
        $data['source'] = strtolower(trim($data['source']));
        $data['raw_status'] = trim($data['raw_status']); // preserve case
        $data['match_type'] = strtolower(trim($data['match_type']));
        $data['status'] = trim($data['status']);
        $data['problem_mode'] = strtolower(trim($data['problem_mode']));
        $data['problem_keyword'] = ! empty($data['problem_keyword'])
            ? trim($data['problem_keyword'])
            : null;
        $data['problem_match_type'] = strtolower(trim($data['problem_match_type'] ?? 'contains'));
        if (! in_array($data['problem_match_type'], TrackingStatusRule::PROBLEM_MATCH_TYPES, true)) {
            $data['problem_match_type'] = 'contains';
        }
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function duplicateExists(array $data, ?int $ignoreId = null): bool
    {
        return TrackingStatusRule::where('source', $data['source'])
            ->where('raw_status', $data['raw_status'])
            ->where('match_type', $data['match_type'])
            ->where('problem_mode', $data['problem_mode'])
            ->where('status', $data['status'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Daftar source valid = SOURCES lama + semua key dari export_templates aktif.
     * Jika admin menambah template baru (mis. idxeveropro), source itu otomatis
     * valid untuk aturan tracking.
     */
    private function validSources(): array
    {
        $fromTemplates = ExportTemplate::where('is_active', true)->pluck('key')->all();
        return array_unique(array_merge(TrackingStatusRule::SOURCES, $fromTemplates));
    }
}
