<?php

namespace App\Services;

use App\Models\OrderOnlineImportBatch;
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
 *  - spx                                          → template SPX
 *  - undeliverable                                → TIDAK ikut export (label khusus)
 *
 * Hanya order berstatus `real` / `tembakan` yang diekspor; order lain ditandai
 * `stock_note` dan dilewati. Saat export, stok produk dikurangi lewat jurnal
 * `stock_movements` (reference `order_online`, idempotent per order).
 *
 * Order dikelompokkan per gudang (lihat `warehouseFor()`): KSP→GTM, SH→Aurora,
 * selain itu → sender. Satu gudang = 1 file .xlsx langsung; ≥ 2 gudang =
 * 1 ZIP berisi file per gudang (karena alamat pickup tiap gudang berbeda).
 *
 * Kolom "Kelurahan" (FLIK) dibiarkan kosong karena data mentah tidak menyediakannya.
 * "Total Nilai Barang / Total Nilai COD" diisi product_price.
 * "Kecamatan" diisi langsung dari kolom subdistrict.
 * Khusus SPX: nomor HP dinormalisasi mulai dari "8" (tanpa 0/62/+62) dan
 * provinsi/kota/kecamatan ditulis CAPSLOCK.
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

    /** Mapping kode produk → kode gudang. */
    public const WAREHOUSE_BY_PRODUCT = [
        'KSP' => 'GTM',
        'SH' => 'Aurora',
    ];

    public function __construct(
        private readonly CourierRuleService $couriers = new CourierRuleService,
        private readonly StockService $stock = new StockService,
    ) {}

    /**
     * Couriers yang masuk ke sebuah template export.
     */
    public function couriersForTemplate(string $template): array
    {
        return match ($template) {
            self::TEMPLATE_FLIK => self::FLIK_COURIERS,
            self::TEMPLATE_SICEPAT => ['sicepat'],
            self::TEMPLATE_SPX => ['spx'],
            default => [],
        };
    }

    /**
     * Kode gudang untuk sebuah order. KSP→GTM, SH→Aurora, selain itu sender.
     */
    public function warehouseFor(?string $productCode, ?string $sender): string
    {
        $code = strtoupper(trim((string) $productCode));
        if ($code !== '') {
            $code = explode('+', $code)[0];
        }

        return self::WAREHOUSE_BY_PRODUCT[$code] ?? ($sender ?? '');
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
            ->when($courier, fn ($q) => $q->where('courier', $courier), fn ($q) => $q->whereIn('courier', $this->couriersForTemplate($template)))
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

    protected function writeRows($sheet, string $template, $orders, ?string $sender = null): void
    {
        $rows = match ($template) {
            self::TEMPLATE_FLIK => $this->flikRows($orders, $sender),
            self::TEMPLATE_SICEPAT => $this->sicepatRows($orders),
            self::TEMPLATE_SPX => $this->spxRows($orders),
            default => throw new \InvalidArgumentException("Template tidak dikenal: {$template}"),
        };

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValue([$c + 1, $r + 1], $value);
            }
        }
    }

    // ─── FLIK ─────────────────────────────────────────────────

    protected function flikRows($orders, ?string $sender = null): array
    {
        $rows = [[
            'Kode Warehouse',
            'Nama Pelanggan',
            'No HP Pelanggan (mulai dengan "62")',
            'No HP Pelanggan (mulai dengan "8")',
            'Alamat: Lengkap',
            'Alamat: Provinsi',
            'Alamat: Kota',
            'Alamat: Kecamatan',
            'Alamat: Kelurahan',
            'Alamat: Kode Pos',
            'Alamat: Catatan Kurir',
            'Total Nilai Barang / Total Nilai COD',
            'Panjang Barang (cm)',
            'Lebar Barang (cm)',
            'Tinggi Barang (cm)',
            'Berat (kg)',
            'Nama Produk',
        ]];

        foreach ($orders as $o) {
            $rows[] = [
                $this->warehouseFor($o->product_code, $sender),
                $o->customer_name,
                $o->phone_normalized,
                $this->phoneWithoutCountryCode($o->phone_normalized),
                $o->address,
                $o->province,
                $o->city,
                $o->subdistrict,
                '',
                $o->postal_code,
                $o->courier_note ?: self::DEFAULT_COURIER_NOTE,
                $o->product_price,
                self::PACK_DIMENSIONS[0],
                self::PACK_DIMENSIONS[1],
                self::PACK_DIMENSIONS[2],
                $o->weight,
                $o->product_name,
            ];
        }

        return $rows;
    }

    // ─── SiCepat ──────────────────────────────────────────────

    protected function sicepatRows($orders): array
    {
        $rows = [[
            'Penerima',
            'No.HP Penerima',
            'Jumlah Paket',
            'No.Referensi (Maksimal 50 Karakter)',
            'Alamat Penerima',
            'Kecamatan',
            'Kota/Kabupaten',
            'Kode Pos',
            'Layanan',
            'Jenis Paket',
            'Isi Paket',
            'Berat Paket (Kg)',
            'Panjang Paket',
            'Lebar Paket',
            'Tinggi Paket',
            'Harga Paket',
            'Packing Kayu',
            'Proteksi Paket?',
            'Total COD',
            'COD Ongkir?',
            'Catatan Pengiriman',
            'Tipe DO Balik',
            'Tipe Alamat DO Balik',
            'Alamat DO Balik',
            'Kecamatan DO Balik',
            'Kota/Kabupaten DO Balik',
            'Kode Pos DO Balik',
        ]];

        foreach ($orders as $o) {
            $rows[] = [
                $o->customer_name,
                $o->phone_normalized,
                $o->quantity,
                mb_substr($o->order_id, 0, 50),
                $o->address,
                $o->subdistrict,
                $o->city,
                $o->postal_code,
                '',
                'Barang',
                $o->product_name,
                $o->weight,
                self::PACK_DIMENSIONS[0],
                self::PACK_DIMENSIONS[1],
                self::PACK_DIMENSIONS[2],
                $o->product_price,
                '',
                '',
                $o->is_cod ? $o->product_price : '',
                '',
                $o->courier_note ?: self::DEFAULT_COURIER_NOTE,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $rows;
    }

    // ─── SPX ──────────────────────────────────────────────────

    protected function spxRows($orders): array
    {
        $rows = [[
            '*Nomor Pesanan',
            '*Nama Penerima // *Recipient Name',
            '*Nomor Telepon Penerima // *Recipient Phone',
            '*Alamat Lengkap // *Detail Address',
            '*Provinsi // *Province',
            '*Kota // *City',
            '*Kecamatan // *District',
            '*Kode Pos // *Postal Code',
            '*Berat Paket (KG) // *Parcel Weight (KG)',
            '*Harga Barang // *Parcel Value',
            '*COD? (Paket COD/Bukan Paket COD) // *COD? (COD Parcel//Non-COD Parcel)',
            '*Nominal COD yang harus ditagihkan ke Penerima // * COD Amount',
            '*Asuransi (Y/N) / *Insurance (Y/N)',
            'Panjang Paket (CM) // Parcel Length (CM)',
            'Lebar Paket (CM) // Parcel Width (CM)',
            'Tinggi Paket (CM) // Parcel Height (CM)',
            '*Nama Barang // *Item Name',
            'Jumlah Barang // Item Quantity',
            'Harga Barang // Item Price',
            'Nomer Referensi Pembeli // Customer Reference Number',
            '*Metode Pembayaran // *Payment Method',
            'Instruksi Pengiriman // Delivery Instruction',
        ]];

        foreach ($orders as $o) {
            $rows[] = [
                $o->order_id,
                $o->customer_name,
                $this->phoneSpx($o->phone_normalized),
                $o->address,
                mb_strtoupper((string) $o->province),
                mb_strtoupper((string) $o->city),
                mb_strtoupper((string) $o->subdistrict),
                $o->postal_code,
                $o->weight,
                $o->product_price,
                $o->is_cod ? 'Y' : 'N',
                $o->is_cod ? $o->product_price : '',
                'N',
                self::PACK_DIMENSIONS[0],
                self::PACK_DIMENSIONS[1],
                self::PACK_DIMENSIONS[2],
                $o->product_name,
                $o->quantity,
                $o->product_price,
                $o->order_id,
                strtoupper($o->payment_method ?? ''),
                $o->courier_note ?: self::DEFAULT_COURIER_NOTE,
            ];
        }

        return $rows;
    }

    protected function phoneWithoutCountryCode(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        return str_starts_with($phone, '62') ? substr($phone, 2) : $phone;
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
