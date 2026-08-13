<?php

namespace App\Services;

use App\Models\ExportTemplateMapping;
use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ShippingOrder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Export shipping_orders per-batch ke template aggregator (FLIK / SiCepat / SPX)
 * dalam format Excel .xlsx (atau ZIP bila memuat lebih dari satu gudang).
 *
 * Pemetaan courier → template:
 *  - flix-tf, flix-idx, flix-sicepat, flix-spx → template FLIK
 *  - sicepat                                     → template SiCepat
 *  - spx                                         → template SPX
 *  - undeliverable                               → TIDAK ikut export (label khusus)
 *
 * Hanya order berstatus `real` / `tembakan` yang diekspor; order lain ditandai
 * `stock_note` dan dilewati. Order yang sudah punya resi (`awb` terisi) TIDAK ikut
 * diekspor (sudah dikirim = tidak boleh di-reserve/ekspor ulang). Saat export, stok
 * produk dikurangi lewat jurnal `stock_movements` (reference `order_online`, idempotent
 * per order).
 *     * Order dikelompokkan per gudang (lihat `warehouseFor()`): KSP→Aurora, SH→GTM,
     * selain itu → sender. Satu gudang = 1 file .xlsx langsung; ≥ 2 gudang =
     * 1 ZIP berisi file per gudang (karena alamat pickup tiap gudang berbeda).
 *
 * Kolom "Kelurahan" (FLIK) dibiarkan kosong karena data mentah tidak menyediakannya.
 * "Total Nilai Barang / Total Nilai COD" diisi amount (gross_revenue CSV).
 * "Kecamatan" diisi langsung dari kolom subdistrict.
 * Khusus SPX: nomor HP dinormalisasi mulai dari "8" (tanpa 0/62/+62) dan
 * provinsi/kota/kecamatan ditulis CAPSLOCK.
 *
 * Sejak 12 Agustus 2026 pemetaan kolom TIDAK lagi hardcoded di sini: header &
 * sumber isi tiap kolom diambil dari tabel `export_template_mappings` (menu
 * Aturan Export). Admin meng-upload template CSV lalu mencocokkan tiap header
 * dengan kolom `shipping_orders`, nilai khusus (computed), teks tetap, atau
 * kosong. Registry sumber ada di ExportMappingService (COLUMNS/COMPUTED).
 */
class OrderTemplateExportService
{
    public const TEMPLATE_FLIK = 'flik';

    public const TEMPLATE_SICEPAT = 'sicepat';

    public const TEMPLATE_SPX = 'spx';

    public const FLIK_COURIERS = ['flix-tf', 'flix-idx', 'flix-sicepat', 'flix-spx'];

    public const TEMPLATES = [
        self::TEMPLATE_FLIK,
        self::TEMPLATE_SICEPAT,
        self::TEMPLATE_SPX,
    ];

    /** Catatan kurir default yang ditulis ke kolom catatan kurir tiap template. */
    public const DEFAULT_COURIER_NOTE = 'HUBUNGI KONSUMEN SEBELUM DIKIRIM';

    /** Dimensi paket default (panjang, lebar, tinggi) dalam cm. */
    public const PACK_DIMENSIONS = [10, 8, 6];

    /** Mapping kode produk → kode gudang. KSP→Aurora, SH→GTM, selain itu → sender. */
    public const WAREHOUSE_BY_PRODUCT = [
        'KSP' => 'Aurora',
        'SH' => 'GTM',
    ];

    /** Cache nama gudang utama per kode produk (anti N+1 per instance). */
    private array $primaryWarehouseByCode = [];

    public function __construct(
        private readonly CourierRuleService $couriers = new CourierRuleService,
        private readonly StockService $stock = new StockService,
        private readonly ExportMappingService $mappings = new ExportMappingService,
    ) {}

    /**
     * Couriers yang masuk ke sebuah template export — dari DB (tabel
     * `export_templates.couriers`), fallback ke legacy bila row terhapus.
     */
    public function couriersForTemplate(string $template): array
    {
        return $this->mappings->couriersForTemplate($template);
    }

    /**
     * Kode gudang untuk sebuah order — mengikuti gudang UTAMA produk
     * (pivot `product_inventory.is_primary`). Produk tanpa gudang utama
     * jatuh ke mapping kode lama (KSP→Aurora, SH→GTM), lalu sender.
     */
    public function warehouseFor(?string $productCode, ?string $sender): string
    {
        $code = strtoupper(trim((string) $productCode));
        if ($code !== '') {
            $code = explode('+', $code)[0];
        }

        if ($code !== '' && ! array_key_exists($code, $this->primaryWarehouseByCode)) {
            $this->primaryWarehouseByCode[$code] = Product::where('code', $code)
                ->first()?->primaryInventory?->first()?->name;
        }

        $name = $this->primaryWarehouseByCode[$code] ?? null;

        return $name ?: (self::WAREHOUSE_BY_PRODUCT[$code] ?? ($sender ?? ''));
    }

    /**
     * Generate file Excel (atau ZIP multi-gudang) dan kirim sebagai unduhan.
     *
     * Sebelum menulis file, stok produk order yang layak diekspor dikurangi
     * (jurnal `order_online`). Order tanpa product match / stok kurang ditandai
     * `stock_note` dan TIDAK ikut di file.
     */
    public function download(OrderOnlineImportBatch $batch, string $template, ?string $courier = null): StreamedResponse
    {
        $orders = ShippingOrder::where('order_online_import_batch_id', $batch->id)
            ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
            ->where(fn ($q) => $q->whereNull('awb')->orWhere('awb', ''))
            ->when($courier, fn ($q) => $q->where('courier', $courier), fn ($q) => $q->whereIn('courier', $this->couriersForTemplate($template)))
            ->with('variant')
            ->orderBy('order_id')
            ->get();

        $exportable = $this->reserveStock($orders);

        $groups = $exportable->groupBy(fn ($o) => $this->warehouseFor($o->product_code, $batch->sender));

        if ($groups->count() <= 1) {
            return $this->streamXlsx(
                $this->buildSpreadsheet($batch, $template, $groups->first() ?? collect(), $batch->sender),
                $this->filename($template, $courier, $groups->keys()->first(), $batch->id),
            );
        }

        return $this->streamZip($groups, $template, $courier, $batch);
    }

    /**
     * Validasi & kurangi stok untuk order yang layak, kembalikan order yang masuk file.
     *
     * @param  Collection<int, ShippingOrder>  $orders
     * @return Collection order yang berhasil dicatat (stok cukup & terhubung produk)
     */
    protected function reserveStock(Collection $orders): Collection
    {
        $exportable = collect();
        $userId = auth()->id();

        foreach ($orders as $order) {
            if (! $order->product_variant_id) {
                $order->update(['stock_note' => 'Produk tidak dikenal (kode tidak terdaftar)']);

                continue;
            }

            try {
                $this->stock->recordOutWithPackaging(
                    $order->product_variant_id,
                    now()->format('Y-m-d'),
                    max(1, $order->quantity),
                    'order_online',
                    $order->id,
                    'Order online '.$order->order_id,
                    $userId,
                );
                $order->update(['stock_note' => null]);
                $exportable->push($order);
            } catch (\RuntimeException $e) {
                $order->update(['stock_note' => $e->getMessage()]);
            }
        }

        return $exportable;
    }

    protected function buildSpreadsheet(OrderOnlineImportBatch $batch, string $template, Collection $orders, ?string $sender = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $this->writeRows($spreadsheet->getActiveSheet(), $template, $orders, $sender);

        return $spreadsheet;
    }

    protected function filename(string $template, ?string $courier, ?string $warehouse, int $batchId): string
    {
        $parts = [date('Ymd'), $template];
        if ($courier) {
            $parts[] = $courier;
        }
        $parts[] = $warehouse ?? 'unknown';
        $parts[] = $batchId;

        return implode('_', $parts).'.xlsx';
    }

    protected function streamXlsx(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bungkus beberapa file .xlsx (satu per gudang) ke dalam satu ZIP.
     */
    protected function streamZip(Collection $groups, string $template, ?string $courier, OrderOnlineImportBatch $batch): StreamedResponse
    {
        $tmpDir = sys_get_temp_dir();
        $zipPath = $tmpDir.'/'.uniqid('export_', true).'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

        foreach ($groups as $warehouse => $group) {
            $xlsxPath = $tmpDir.'/'.uniqid('row_', true).'.xlsx';
            (new Xlsx($this->buildSpreadsheet($batch, $template, $group, $batch->sender)))->save($xlsxPath);
            $zip->addFromString($this->filename($template, $courier, $warehouse, $batch->id), file_get_contents($xlsxPath));
            @unlink($xlsxPath);
        }

        $zip->close();

        $zipName = date('Ymd').'_'.$template.($courier ? '_'.$courier : '').'_'.$batch->id.'.zip';

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Tulis baris (header + data) berdasarkan mapping dinamis dari tabel
     * `export_template_mappings` — bukan array hardcoded.
     */
    protected function writeRows($sheet, string $template, $orders, ?string $sender = null): void
    {
        $rows = $this->buildRows($template, $orders, $sender);

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValue([$c + 1, $r + 1], $value);
            }
        }
    }

    /**
     * Bangun baris export (header + data) dari mapping dinamis.
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws \RuntimeException bila mapping template belum diatur di menu Aturan Export
     */
    protected function buildRows(string $template, $orders, ?string $sender = null): array
    {
        $mapping = $this->mappings->mappingFor($template);
        if ($mapping->isEmpty()) {
            throw new \RuntimeException(
                "Mapping export untuk template '{$template}' belum diatur. Buka menu Aturan Export lalu upload template CSV."
            );
        }

        $rows = [$mapping->map(fn ($m) => $m->header)->all()];

        foreach ($orders as $o) {
            $row = [];
            foreach ($mapping as $m) {
                $row[] = $this->resolveCell($m, $o, $sender);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Isi satu sel dari aturan mapping (sumber: kolom / nilai khusus / teks tetap / kosong).
     */
    protected function resolveCell(ExportTemplateMapping $m, ShippingOrder $o, ?string $sender)
    {
        return match ($m->source_type) {
            'column' => $this->columnValue($o, $m->source_value),
            'computed' => $this->computedValue((string) $m->source_value, $o, $sender),
            'static' => $m->source_value,
            default => '',
        };
    }

    /**
     * Nilai kolom shipping_orders (hanya dari registry COLUMNS agar aman).
     * Tanggal diformat string; selain itu dikembalikan apa adanya.
     */
    protected function columnValue(ShippingOrder $o, ?string $column)
    {
        if ($column === null || ! in_array($column, array_keys(ExportMappingService::COLUMNS), true)) {
            return '';
        }

        $value = $o->{$column};
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }

        return $value;
    }

    /**
     * Nilai khusus (computed) — transform yang tidak bisa dinyatakan sebagai
     * kolom langsung. Key harus ada di ExportMappingService::COMPUTED.
     */
    protected function computedValue(string $key, ShippingOrder $o, ?string $sender)
    {
        return match ($key) {
            'warehouse' => $this->warehouseFor($o->product_code, $sender),
            'product_name_display' => $this->productDisplayName($o),
            'phone_spx' => $this->phoneSpx($o->phone_normalized),
            'weight_1' => 1,
            'pack_length' => self::PACK_DIMENSIONS[0],
            'pack_width' => self::PACK_DIMENSIONS[1],
            'pack_height' => self::PACK_DIMENSIONS[2],
            'default_courier_note' => $o->courier_note ?: self::DEFAULT_COURIER_NOTE,
            'cod_flag' => $o->is_cod ? 'Y' : 'N',
            'cod_amount' => $o->is_cod ? $o->amount : '',
            'payment_method_upper' => strtoupper((string) $o->payment_method),
            'province_upper' => mb_strtoupper((string) $o->province),
            'city_upper' => mb_strtoupper((string) $o->city),
            'district_upper' => mb_strtoupper((string) $o->subdistrict),
            'order_id_50' => mb_substr((string) $o->order_id, 0, 50),
            default => '',
        };
    }

    /**
     * Nama produk untuk kolom export. Untuk kacamata (KMP/KSP/KBJ) ditulis
     * dalam format `<nama> +<power> <qty> pcs` (power dari product_variants),
     * mis. "Kacamata Sporty +1.50 2 pcs". Produk lain memakai product_name apa adanya.
     */
    protected function productDisplayName(ShippingOrder $o): string
    {
        $base = strtoupper(trim(explode('+', (string) $o->product_code)[0]));
        if (! in_array($base, StockService::KACAMATA_CODES, true)) {
            return (string) $o->product_name;
        }

        $power = (float) ($o->variant?->power ?? 0);
        if ($power <= 0) {
            return (string) $o->product_name;
        }

        $name = preg_replace('/\s+\d+\s*pcs$/i', '', trim((string) $o->product_name));

        return trim($name).' '.sprintf('+%.2f', $power).' '.max(1, (int) $o->quantity).' pcs';
    }

    /**
     * Normalisasi nomor HP untuk SPX: mulai dari "8", tanpa 0/62/+62.
     * (phone_normalized di DB selalu format 62..., jadi hasilnya 811...)
     */
    protected function phoneSpx(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($phone, '62')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }
}
