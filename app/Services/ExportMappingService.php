<?php

namespace App\Services;

use App\Models\ExportTemplate;
use App\Models\ExportTemplateMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Kelola mapping export dinamis (tabel `export_template_mappings`).
 *
 * Untuk tiap template courier (flik/sicepat/spx), admin meng-upload template
 * CSV (header baris pertama = kolom file) lalu mencocokkan tiap header dengan
 * SUMBER isi:
 *  - `column`   → kolom `shipping_orders` (lihat COLUMNS)
 *  - `computed` → nilai khusus hasil perhitungan (lihat COMPUTED)
 *  - `static`   → teks tetap yang diketik admin
 *  - `empty`    → dibiarkan kosong
 *
 * Registry COLUMNS/COMPUTED adalah satu-satunya sumber kebenaran yang dipakai
 * dropdown UI DAN resolver di OrderTemplateExportService (tidak boleh beda).
 *
 * Sejak 12 Agustus 2026 template bersifat BEBAS: tabel `export_templates`
 * memuat master (key/name/couriers/is_active); `export_template_mappings.template`
 * menyimpan `key`. Admin bisa buat template baru (mis. JNE) yang langsung
 * muncul sebagai opsi export di halaman Data Mentah.
 */
class ExportMappingService
{
    /** Key template bawaan (safety: fallback couriers bila row terhapus). */
    public const LEGACY_COURIERS = [
        'flik' => ['flix-tf', 'flix-idx', 'flix-sicepat', 'flix-spx'],
        'sicepat' => ['sicepat'],
        'spx' => ['spx'],
    ];

    /** @var array<string, ExportTemplate>|null cache per request (key → model) */
    private ?array $templateCache = null;

    /** Kolom shipping_orders yang boleh dipakai sebagai sumber isi. */
    public const COLUMNS = [
        'order_id' => 'Order ID',
        'customer_name' => 'Nama Pelanggan',
        'phone' => 'Telp (mentah)',
        'phone_normalized' => 'Telp (62...)',
        'address' => 'Alamat',
        'province' => 'Provinsi',
        'city' => 'Kota',
        'subdistrict' => 'Kecamatan',
        'postal_code' => 'Kode Pos',
        'payment_method' => 'Metode Bayar',
        'courier' => 'Courier',
        'courier_note' => 'Catatan Kurir',
        'product_name' => 'Nama Produk',
        'product_code' => 'Kode Produk',
        'quantity' => 'Jumlah',
        'weight' => 'Berat (kg)',
        'amount' => 'Nilai Barang (amount)',
        'shipping_cost' => 'Ongkir',
        'is_cod' => 'COD?',
        'awb' => 'Resi (AWB)',
        'aggregator_status' => 'Status Aggregator',
        'delivered_at' => 'Tgl Terkirim',
        'handled_by' => 'Dihandle CS',
        'status' => 'Status Order',
        'meta_account' => 'Meta Account',
    ];

    /** Nilai khusus (dihitung saat export) yang boleh dipakai sebagai sumber. */
    public const COMPUTED = [
        'warehouse' => 'Kode Warehouse (KSP→GTM, SH→Aurora, lain→sender)',
        'product_name_display' => 'Nama Produk +power (kacamata)',
        'phone_spx' => 'Telp mulai "8" (format SPX)',
        'weight_1' => 'Berat tetap 1',
        'pack_length' => 'Panjang paket 10 cm',
        'pack_width' => 'Lebar paket 8 cm',
        'pack_height' => 'Tinggi paket 6 cm',
        'default_courier_note' => 'Catatan kurir default',
        'cod_flag' => 'COD? Y / N',
        'cod_amount' => 'Nominal COD (jika COD)',
        'payment_method_upper' => 'Metode bayar CAPSLOCK',
        'province_upper' => 'Provinsi CAPSLOCK',
        'city_upper' => 'Kota CAPSLOCK',
        'district_upper' => 'Kecamatan CAPSLOCK',
        'order_id_50' => 'Order ID (maks 50 karakter)',
    ];

    public const SOURCE_TYPES = ['column', 'computed', 'static', 'empty'];

    /** @var array<string, Collection<int, ExportTemplateMapping>>|null cache per request */
    private ?array $cache = null;

    /**
     * Daftar template aktif (master), urut dari id.
     *
     * @return Collection<int, ExportTemplate>
     */
    public function templates(): Collection
    {
        return ExportTemplate::query()->where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Courier yang memakai sebuah template saat export (dari DB, key → couriers).
     * Fallback ke legacy bila row template terhapus (safety net).
     *
     * @return array<int, string>
     */
    public function couriersForTemplate(string $templateKey): array
    {
        $template = $this->template($templateKey);
        if ($template !== null && ! empty($template->couriers)) {
            return $template->couriers;
        }

        return self::LEGACY_COURIERS[$templateKey] ?? [];
    }

    /**
     * Ambil template by key (cache per request).
     */
    public function template(string $templateKey): ?ExportTemplate
    {
        if ($this->templateCache === null) {
            $this->templateCache = ExportTemplate::query()->get()->keyBy('key')->all();
        }

        return $this->templateCache[$templateKey] ?? null;
    }

    /**
     * Mapping aktif sebuah template, urut dari kolom pertama (index 0).
     *
     * @return Collection<int, ExportTemplateMapping>
     */
    public function mappingFor(string $template): Collection
    {
        if ($this->cache === null) {
            $this->cache = ExportTemplateMapping::query()
                ->where('is_active', true)
                ->orderBy('column_index')
                ->get()
                ->groupBy('template')
                ->all();
        }

        return $this->cache[$template] ?? collect();
    }

    /**
     * Baca baris header dari template CSV yang di-upload.
     *
     * @return array<int, string>
     */
    public function parseTemplateFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka file template: '.$filePath);
        }
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            throw new \RuntimeException('File template kosong.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);

        // Bersihkan BOM di header pertama
        if (isset($headers[0])) {
            $headers[0] = trim(preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? '', " \t\n\r\0\x0B");
        }

        // Buang kolom trailing yang benar-benar kosong (biasanya artefak trailing comma)
        while ($headers !== [] && end($headers) === '') {
            array_pop($headers);
        }

        if ($headers === []) {
            throw new \RuntimeException('Baris header template kosong.');
        }

        return $headers;
    }

    /**
     * Cocokkan header baru dengan mapping lama (by nama header, case-insensitive)
     * agar upload ulang template tidak menghilangkan mapping yang sudah diatur.
     *
     * @param  array<int, string>  $newHeaders
     * @return array<int, array{header:string, source_type:string, source_value:string|null}>
     */
    public function matchHeaders(string $template, array $newHeaders): array
    {
        $old = $this->mappingFor($template)
            ->mapWithKeys(fn ($m) => [$this->normalize($m->header) => $m])
            ->all();

        $result = [];
        foreach ($newHeaders as $i => $header) {
            $match = $old[$this->normalize($header)] ?? null;
            $result[] = [
                'header' => $header,
                'source_type' => $match?->source_type ?? 'empty',
                'source_value' => $match?->source_value,
            ];
        }

        return $result;
    }

    /**
     * Ganti seluruh mapping sebuah template dalam satu transaksi.
     *
     * @param  array<int, array{column_index:int, header:string, source_type:string, source_value:string|null}>  $items
     */
    public function saveMapping(string $template, array $items): void
    {
        DB::transaction(function () use ($template, $items) {
            ExportTemplateMapping::where('template', $template)->delete();

            foreach ($items as $item) {
                ExportTemplateMapping::create([
                    'template' => $template,
                    'column_index' => (int) $item['column_index'],
                    'header' => (string) $item['header'],
                    'source_type' => $item['source_type'],
                    'source_value' => $item['source_value'] !== '' ? $item['source_value'] : null,
                    'is_active' => true,
                ]);
            }
        });
    }

    /**
     * Buat template baru (key auto-slug dari name) + mapping awal.
     *
     * @param  array<int, string>  $couriers
     * @param  array<int, array{column_index:int, header:string, source_type:string, source_value:string|null}>  $items
     */
    public function createTemplate(string $name, array $couriers, array $items): ExportTemplate
    {
        $key = $this->uniqueKey($name);

        $template = DB::transaction(function () use ($key, $name, $couriers, $items) {
            $template = ExportTemplate::create([
                'key' => $key,
                'name' => $name,
                'couriers' => $couriers !== [] ? array_values($couriers) : [$key],
                'is_active' => true,
            ]);
            $this->saveMapping($template->key, $items);

            return $template;
        });

        return $template;
    }

    /**
     * Perbarui nama/couriers + ganti mapping sebuah template.
     *
     * @param  array<int, string>  $couriers
     * @param  array<int, array{column_index:int, header:string, source_type:string, source_value:string|null}>  $items
     */
    public function updateTemplate(ExportTemplate $template, string $name, array $couriers, array $items): void
    {
        DB::transaction(function () use ($template, $name, $couriers, $items) {
            $template->update([
                'name' => $name,
                'couriers' => $couriers !== [] ? array_values($couriers) : [$template->key],
            ]);
            $this->saveMapping($template->key, $items);
        });
    }

    /**
     * Hapus template permanen (mapping ikut terhapus) dalam 1 transaksi.
     * Safety net: bila template hilang, export memakai pesan jelas & rule
     * courier fallback (`spx`) tetap berjalan.
     */
    public function deleteTemplate(ExportTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            ExportTemplateMapping::where('template', $template->key)->delete();
            $template->delete();
        });
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name, '-') ?: 'template';
        $key = $base;
        $i = 2;
        while (ExportTemplate::where('key', $key)->exists()) {
            $key = $base.'-'.$i++;
        }

        return $key;
    }

    private function normalize(string $header): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($header)) ?? '');
    }
}
