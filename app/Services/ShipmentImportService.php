<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Unified service merging shipping data from 3 aggregators
 * (FLIK, SiCepat, SPX) into a single table.
 *
 * Storage strategy: daily UPSERT.
 * - Natural key: (source, tracking_number).
 * - Pull daily (rolling 1 month), overwrite old rows when status changed,
 *   insert new ones.
 * - Every status change is recorded in shipment_status_histories.
 * - Rows whose product_name cannot be matched to a product are skipped
 *   (not saved) and reported as unmatched.
 */
class ShipmentImportService
{
    public function __construct(
        private readonly ProductNameMatcher $matcher = new ProductNameMatcher,
        private readonly StockService $stock = new StockService,
    ) {}

    /**
     * Read & parse a CSV into normalized rows (not yet persisted).
     *
     * @return array{source:string, data:Collection, skips:array}
     */
    public function parse(string $filePath): array
    {
        $rows = $this->readCsv($filePath);
        if (empty($rows) || count($rows) < 1) {
            throw new \RuntimeException('File CSV kosong atau tidak memiliki baris data.');
        }

        $source = $this->detectSource($rows[0]);
        $headers = $this->cleanHeaders($rows[0]);
        $colMap = $this->mapHeaders($headers, $source);

        $data = collect();
        $skips = [];
        $seen = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $lineNo = $i + 2; // 1-based + header
            $trackingNumber = $this->value($row, $colMap, 'tracking_number');
            $trackingNumber = trim((string) $trackingNumber);

            if ($trackingNumber === '') {
                continue;
            }

            // Dedupe within a single file: keep last row for same tracking number
            if (isset($seen[$trackingNumber])) {
                continue;
            }
            $seen[$trackingNumber] = true;

            $parsed = $this->normalizeRow($row, $colMap, $source);
            if ($parsed === null) {
                $skips[] = "Baris {$lineNo}: data tidak lengkap";

                continue;
            }

            $data->push($parsed);
        }

        return [
            'ukuran' => 'file',
            'data' => $data,
            'skips' => $skips,
            'total' => $data->count(),
        ];
    }

    /**
     * Normalize a single CSV row into the unified column structure.
     */
    protected function normalizeRow(array $row, array $colMap, string $source): ?array
    {
        $createdDate = $this->parseDate($this->value($row, $colMap, 'created_date'));
        $trackingNumber = trim($this->value($row, $colMap, 'tracking_number'));

        if ($trackingNumber === '') {
            return null;
        }

        $shippingFee = $this->decimal($this->value($row, $colMap, 'shipping_fee'));
        $parcelValue = $this->decimal($this->value($row, $colMap, 'parcel_value'));
        $codAmount = $this->decimal($this->value($row, $colMap, 'cod_amount'));

        return [
            'source' => $source,
            'tracking_number' => $trackingNumber,
            'order_id' => $this->text($row, $colMap, 'order_id'),
            'courier' => $this->text($row, $colMap, 'courier') ?: $this->defaultCourier($source),
            'service' => $this->text($row, $colMap, 'service'),
            'recipient_name' => $this->text($row, $colMap, 'recipient_name'),
            'phone' => $this->text($row, $colMap, 'phone'),
            'full_address' => $this->text($row, $colMap, 'full_address'),
            'district' => $this->text($row, $colMap, 'district'),
            'city' => $this->text($row, $colMap, 'city'),
            'province' => $this->text($row, $colMap, 'province'),
            'postal_code' => $this->text($row, $colMap, 'postal_code'),
            'product_name' => $this->text($row, $colMap, 'product_name'),
            'quantity' => max(1, (int) $this->decimal($this->value($row, $colMap, 'quantity')) ?: 1),
            'shipping_fee' => $shippingFee,
            'parcel_value' => $parcelValue,
            'cod_amount' => $codAmount,
            'is_cod' => $codAmount > 0 || $this->isCod($this->raw($row, $colMap, 'is_cod')),
            'status' => $this->text($row, $colMap, 'status'),
            'courier_note' => $this->text($row, $colMap, 'courier_note'),
            'created_date' => $createdDate,
            'pickup_date' => $this->parseDate($this->value($row, $colMap, 'pickup_date')),
            'delivered_date' => $this->parseDate($this->value($row, $colMap, 'delivered_date')),
            'source_file' => $source,
        ];
    }

    public function defaultCourier(string $source): string
    {
        return match ($source) {
            'spx' => 'SPX Express',
            'sicepat' => 'SiCepat',
            default => 'FLIK',
        };
    }

    protected function isCod(string $raw): bool
    {
        $raw = strtolower(trim($raw) ?? '');

        return in_array($raw, ['y', 'yes', 'cod', '1', 'true']);
    }

    protected function raw(array $row, array $colMap, string $key): string
    {
        return (string) ($row[$colMap[$key] ?? -1] ?? '');
    }

    protected function text(array $row, array $colMap, string $key): string
    {
        return trim((string) ($row[$colMap[$key] ?? -1] ?? ''));
    }

    protected function value(array $row, array $colMap, string $key)
    {
        return $row[$colMap[$key] ?? -1] ?? '';
    }

    protected function decimal($val): float
    {
        $val = str_replace(['.', ','], ['', ','], (string) $val);
        // Pisahkan dengan koma untuk desimal, titik untuk ribuan
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $val);
    }

    protected function parseDate($val): ?string
    {
        $val = trim((string) $val);
        if ($val === '') {
            return null;
        }

        // Clean spasi waktu
        $val = preg_replace('/[\s]+\d{2}[:.]\d{2}.*$/', '', $val);
        $val = trim($val);

        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'd.m.Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $val);
            if ($dt && $dt->format($format) === $val) {
                return $dt->format('Y-m-d');
            }
        }

        // SPX format "dd-mm-yyyy" sudah tercakup; fallback umum
        try {
            return (new \DateTime($val))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    // ─── CSV / header helpers ─────────────────────────────────

    protected function readCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka file: '.$filePath);
        }
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    protected function cleanHeaders(array $row): array
    {
        return array_values(array_map(fn ($h) => $this->normalizeHeader($h), $row));
    }

    protected function normalizeHeader(string $h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE/', '', $h ?? '');
        $h = strtolower(trim((string) $h));
        $h = preg_replace('/[^a-z0-9.]/u', ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);

        return trim($h);
    }

    protected function detectSource(array $headerRow): string
    {
        $joined = implode(' ', $this->cleanHeaders($headerRow));

        if (str_contains($joined, 'tracking no') && (str_contains($joined, 'parcel value') || str_contains($joined, 'recipient phone'))) {
            return 'spx';
        }
        if (str_contains($joined, 'nomor resi') && str_contains($joined, 'isi paket')) {
            return 'sicepat';
        }
        if (str_contains($joined, 'order id') && str_contains($joined, 'awb')) {
            return 'flik';
        }
        if (str_contains($joined, 'tanggal pembuatan') && str_contains($joined, 'nama shopper')) {
            return 'flik';
        }
        if (str_contains($joined, 'sumber') && str_contains($joined, 'nama produk')) {
            return 'flik';
        }

        // Dusuk ke SPX sebagai default
        return 'spx';
    }

    protected function mapHeaders(array $headers, string $source): array
    {
        $aliases = $this->aliasMap($source);

        $result = [];
        foreach ($headers as $i => $lower) {
            foreach ($aliases as $key => $keywords) {
                if (isset($result[$key])) {
                    continue;
                }
                foreach ($keywords as $kw) {
                    if ($lower === $kw || str_contains($lower, $kw)) {
                        $result[$key] = $i;
                        break 2;
                    }
                }
            }
        }

        return $result;
    }

    protected function aliasMap(string $source): array
    {
        $common = [
            'recipient_name' => ['recipient name', 'nama shopper', 'nama penerima', 'nama', 'penerima'],
            'phone' => ['recipient phone', 'no hp', 'no telp', 'no. hp', 'telepon', 'phone', 'telp', 'hp'],
            'full_address' => ['recipient detail address', 'alamat lengkap', 'alamat penerima', 'alamat', 'address'],
            'district' => ['recipient district', 'kecamatan', 'district', 'sub district'],
            'city' => ['recipient city', 'kota', 'city', 'kabupaten', 'kab'],
            'province' => ['recipient province', 'provinsi', 'province', 'propinsi'],
            'postal_code' => ['recipient postal code', 'kode pos', 'postal code', 'kode pos'],
            'product_name' => ['item in parcel', 'isi paket', 'nama produk', 'item', 'produk', 'product', 'nama produk'],
            'quantity' => ['no. of item', 'jumlah isi paket', 'jumlah', 'qty', 'quantity'],
            'status' => ['tracking status', 'status'],
            'shipping_fee' => ['actual shipping fee', 'harga ongkir', 'ongkir', 'shipping cost', 'biaya kirim'],
            'parcel_value' => ['parcel value', 'harga paket', 'harga setelah dis', 'total biaya', 'value', 'total', 'harga'],
            'cod_amount' => ['cod amount', 'nominal cod', 'cod'],
            'created_date' => ['create time', 'tanggal pembuatan', 'tanggal', 'tgl', 'date', 'tanggal dibuat'],
            'pickup_date' => ['actual pickup/drop off time', 'actual pickup', 'tanggal dipickup', 'pickup'],
            'delivered_date' => ['delivered time', 'tanggal terkirim', 'delivered'],
            'service' => ['service', 'layanan', 'jenis layanan'],
            'courier_note' => ['delivery failed reason', 'catatan kurir', 'delivery failed', 'failed reason', 'catatan', 'keterangan'],
            'order_id' => ['order id', 'customer reference no', 'nomor referensi', 'order'],
            'is_cod' => ['cod collection', 'cod', 'tipe pembayaran', 'tipe bayar', 'pembayaran'],
        ];

        if ($source === 'sicepat') {
            $common['tracking_number'] = ['nomor resi', 'no resi', 'resi', 'awb'];
        } elseif ($source === 'flik') {
            $common['tracking_number'] = ['awb', 'no resi', 'resi'];
        } else {
            $common['tracking_number'] = ['tracking no', 'no resi', 'resi', 'awb'];
        }

        return $common;
    }

    /**
     * Hitung laporan kecocokan produk untuk preview (tanpa menyimpan).
     *
     * @return array{matched:int, unmatched:array}
     */
    public function matchReport(Collection $rows): array
    {
        $productIndex = $this->matcher->buildIndex();
        $unmatched = [];

        foreach ($rows as $row) {
            $product = $this->matcher->match($row['product_name'] ?? '', $productIndex);
            if ($product === null) {
                $unmatched[] = [
                    'tracking_number' => $row['tracking_number'],
                    'product_name' => $row['product_name'] ?? '',
                ];
            }
        }

        return [
            'matched' => $rows->count() - count($unmatched),
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Process a single file (parse + upsert) inside one transaction.
     *
     * Rows whose product_name cannot be matched to a product are skipped
     * (not saved to shipments) and returned in `unmatched`.
     *
     * @return array{source:string, inserted:int, updated:int, unchanged:int, skipped:int, unmatched:array, matched:int}
     */
    public function import(string $filePath): array
    {
        $parsed = $this->parse($filePath);
        $rows = $parsed['data'];

        if ($rows->isEmpty()) {
            return [
                'source' => null,
                'inserted' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'skipped' => count($parsed['skips']),
                'unmatched' => [],
                'matched' => 0,
            ];
        }

        $source = $rows->first()['source'];

        return DB::transaction(function () use ($rows, $source) {
            $inserted = 0;
            $updated = 0;
            $unchanged = 0;
            $unmatched = [];
            $matched = 0;

            $productIndex = $this->matcher->buildIndex();

            $existing = Shipment::where('source', $source)
                ->whereIn('tracking_number', $rows->pluck('tracking_number')->all())
                ->get()
                ->keyBy('tracking_number');

            $statusHistory = [];

            foreach ($rows as $row) {
                $trackingNumber = $row['tracking_number'];

                // Cocokkan nama produk → jika tak ada, skip (tidak disimpan)
                $product = $this->matcher->match($row['product_name'] ?? '', $productIndex);
                if ($product === null) {
                    $unmatched[] = [
                        'tracking_number' => $trackingNumber,
                        'product_name' => $row['product_name'] ?? '',
                    ];

                    continue;
                }
                $matched++;

                $variant = $product->defaultVariant();
                if ($variant === null) {
                    $unmatched[] = [
                        'tracking_number' => $trackingNumber,
                        'product_name' => $row['product_name'] ?? '',
                    ];

                    continue;
                }

                $row['product_id'] = $product->id;
                $has = $existing->get($trackingNumber);

                if ($has) {
                    $changed = $this->diff($has, $row);
                    if (empty($changed)) {
                        $unchanged++;

                        continue;
                    }

                    $statusChanged = ($row['status'] ?? null) &&
                        ($row['status'] !== $has->status);
                    if ($statusChanged) {
                        $statusHistory[] = [
                            'shipment_id' => $has->id,
                            'status' => $has->status,
                            'courier_note' => $has->courier_note,
                        ];
                    }

                    $has->update($row);
                    $this->stock->recordOutWithPackaging(
                        $variant->id,
                        $row['created_date'] ?? now()->format('Y-m-d'),
                        $row['quantity'] ?: 1,
                        'shipment',
                        $has->id,
                        'Shipment '.$trackingNumber,
                        auth()->id(),
                    );
                    $updated++;
                } else {
                    $model = Shipment::create($row);
                    if ($row['status']) {
                        $statusHistory[] = [
                            'shipment_id' => $model->id,
                            'status' => null,
                            'courier_note' => null,
                        ];
                    }
                    $this->stock->recordOutWithPackaging(
                        $variant->id,
                        $row['created_date'] ?? now()->format('Y-m-d'),
                        $row['quantity'] ?: 1,
                        'shipment',
                        $model->id,
                        'Shipment '.$trackingNumber,
                        auth()->id(),
                    );
                    $inserted++;
                }
            }

            $this->saveStatusHistories($statusHistory);

            return [
                'source' => $source,
                'inserted' => $inserted,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'matched' => $matched,
                'unmatched' => $unmatched,
            ];
        });
    }

    protected function diff(Shipment $current, array $row): array
    {
        $changed = [];
        $strings = [
            'status', 'courier_note', 'recipient_name', 'phone', 'full_address',
            'city', 'province',
        ];
        $floats = ['shipping_fee', 'parcel_value'];
        $dates = ['pickup_date', 'delivered_date'];

        foreach ($strings as $field) {
            if (trim((string) ($current->{$field} ?? '')) !== trim((string) ($row[$field] ?? ''))) {
                $changed[$field] = true;
            }
        }
        foreach ($floats as $field) {
            if ((float) ($current->{$field} ?? 0) !== (float) ($row[$field] ?? 0)) {
                $changed[$field] = true;
            }
        }
        foreach ($dates as $field) {
            $a = $current->{$field} ? $current->{$field}->format('Y-m-d') : '';
            $b = is_object($row[$field] ?? null) ? $row[$field]->format('Y-m-d') : (string) ($row[$field] ?? '');
            if ($a !== $b) {
                $changed[$field] = true;
            }
        }

        return $changed;
    }

    protected function saveStatusHistories(array $history): void
    {
        foreach ($history as $info) {
            ShipmentStatusHistory::create([
                'shipment_id' => $info['shipment_id'],
                'status' => $info['status'],
                'courier_note' => $info['courier_note'],
                'viewed_at' => now(),
            ]);
        }
    }
}
