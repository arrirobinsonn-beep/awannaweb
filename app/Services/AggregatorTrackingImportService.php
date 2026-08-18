<?php

namespace App\Services;

use App\Models\ShippingOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Mengisi kolom tracking `shipping_orders` (awb, aggregator_status, delivered_at)
 * dari file dashboard aggregator (FLIK / SiCepat / SPX) yang di-upload admin.
 *
 * Baris file dihubungkan ke order lewat signature:
 *   Tier 1: phone_normalized + product_id + quantity + alamat (dinormalisasi)
 *   Tier 2 (fallback): phone_normalized + product_id + quantity (bila tier 1 kosong)
 *
 * `aggregator_status` dinormalisasi ke 6 nilai Inggris (ShippingOrder::TRACKING_STATUSES).
 * Ketika status berubah menjadi `returned`, stok yang di-reserve saat export
 * (jurnal `order_online`) dikembalikan lewat StockService::reverseReference.
 *
 * File CSV/xlsx dibaca dengan PhpSpreadsheet sehingga kolom tanggal mengikuti
 * format sel aslinya. Deteksi sumber dari header (pola ShipmentImportService).
 */
class AggregatorTrackingImportService
{
    public function __construct(
        private readonly ProductNameMatcher $matcher = new ProductNameMatcher,
        private readonly StockService $stock = new StockService,
        private readonly TrackingStatusRuleService $statusRules = new TrackingStatusRuleService,
    ) {}

    /**
     * Baca & normalisasi file dashboard menjadi baris tracking (belum disimpan).
     *
     * @return array{source:?string, data:Collection, skips:array, total:int}
     */
    public function parse(string $filePath): array
    {
        $rows = $this->readRows($filePath);
        if (empty($rows)) {
            throw new \RuntimeException('File kosong atau tidak memiliki baris.');
        }

        [$source, $headerIndex] = $this->detectSource($rows);
        $headers = $this->cleanHeaders($rows[$headerIndex]);
        $colMap = $this->mapHeaders($headers, $source);

        $data = collect();
        $skips = [];
        $seen = [];

        foreach (array_slice($rows, $headerIndex + 1) as $i => $row) {
            $lineNo = $headerIndex + $i + 2;
            $awb = trim($this->text($row, $colMap, 'tracking_number'));
            if ($awb === '') {
                continue;
            }

            // Dedupe dalam satu file: simpan baris terakhir utk awb yang sama
            if (isset($seen[$awb])) {
                continue;
            }
            $seen[$awb] = true;

            $parsed = $this->normalizeRow($row, $colMap, $source);
            if ($parsed === null) {
                $skips[] = "Baris {$lineNo}: nomor HP tidak lengkap";

                continue;
            }

            $data->push($parsed);
        }

        return [
            'source' => $source,
            'data' => $data,
            'skips' => $skips,
            'total' => $data->count(),
        ];
    }

    /**
     * Proses satu file di dalam satu transaksi: cocokkan tiap baris ke
     * shipping_orders dan isi awb / aggregator_status / delivered_at.
     *
     * @return array{source:?string,total:int,matched:int,updated:int,stock_returned:int,unmatched:array,ambiguous:array}
     */
    public function import(string $filePath): array
    {
        $parsed = $this->parse($filePath);
        $rows = $parsed['data'];

        if ($rows->isEmpty()) {
            return [
                'source' => $parsed['source'],
                'total' => 0,
                'matched' => 0,
                'updated' => 0,
                'stock_returned' => 0,
                'unmatched' => [],
                'ambiguous' => [],
            ];
        }

        $source = $rows->first()['source'];

        return DB::transaction(function () use ($rows, $source) {
            $productIndex = $this->matcher->buildIndex();

            $candidates = ShippingOrder::whereIn('phone_normalized', $rows->pluck('phone_normalized')->unique()->all())
                ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
                ->get()
                ->groupBy('phone_normalized');

            $matched = 0;
            $updated = 0;
            $stockReturned = 0;
            $unmatched = [];
            $ambiguous = [];

            foreach ($rows as $row) {
                $product = $this->matcher->match($row['product_text'] ?? '', $productIndex);
                $resolved = $this->resolveOrder($candidates->get($row['phone_normalized'], collect()), $product?->id, $row);

                if ($resolved['ambiguous']) {
                    $ambiguous[] = $row['awb'];

                    continue;
                }

                $order = $resolved['order'];
                if ($order === null) {
                    $unmatched[] = [
                        'awb' => $row['awb'],
                        'phone' => $row['phone_normalized'],
                        'product_name' => $row['product_text'] ?? '',
                    ];

                    continue;
                }

                $data = [
                    'awb' => $row['awb'],
                    'aggregator_status' => $row['status'],
                ];
                if ($row['delivered_at'] !== null) {
                    $data['delivered_at'] = $row['delivered_at'];
                }

                $wasReturned = $order->aggregator_status === 'returned';
                $order->update($data);
                $matched++;
                $updated++;

                if ($row['status'] === 'returned' && ! $wasReturned) {
                    $this->stock->reverseReference('order_online', $order->id);
                    $stockReturned++;
                }
            }

            return [
                'source' => $source,
                'total' => $rows->count(),
                'matched' => $matched,
                'updated' => $updated,
                'stock_returned' => $stockReturned,
                'unmatched' => $unmatched,
                'ambiguous' => $ambiguous,
            ];
        });
    }

    /**
     * Cari 1 order yang cocok dengan baris tracking (tier 1 lalu tier 2).
     *
     * @param  Collection<int, ShippingOrder>  $candidates
     * @return array{order:?ShippingOrder,ambiguous:bool}
     */
    protected function resolveOrder(Collection $candidates, ?int $productId, array $row): array
    {
        if ($productId === null) {
            return ['order' => null, 'ambiguous' => false];
        }

        $sameProduct = $candidates->filter(
            fn ($o) => (int) $o->product_id === $productId && (int) $o->quantity === $row['quantity']
        );
        if ($sameProduct->isEmpty()) {
            return ['order' => null, 'ambiguous' => false];
        }

        $byAddress = $sameProduct->filter(
            fn ($o) => $this->normalizeAddress($o->address) === $row['address_norm']
        );
        if ($byAddress->count() === 1) {
            return ['order' => $byAddress->first(), 'ambiguous' => false];
        }
        if ($byAddress->count() > 1) {
            return ['order' => null, 'ambiguous' => true];
        }

        // Tier 2: tanpa alamat, hanya bila unik
        if ($sameProduct->count() === 1) {
            return ['order' => $sameProduct->first(), 'ambiguous' => false];
        }

        return ['order' => null, 'ambiguous' => true];
    }

    /**
     * Normalisasi satu baris dashboard menjadi struktur tracking.
     */
    protected function normalizeRow(array $row, array $colMap, string $source): ?array
    {
        $awb = trim($this->text($row, $colMap, 'tracking_number'));
        $phone = OrderOnlineImportService::normalizePhone($this->text($row, $colMap, 'phone'));
        if ($awb === '' || $phone === '') {
            return null;
        }

        $rawStatus = $this->text($row, $colMap, 'status');
        $status = $this->mapStatus($source, $rawStatus, $this->text($row, $colMap, 'problem'));

        $productText = $this->text($row, $colMap, 'product_name');
        $quantity = (int) $this->decimal($this->value($row, $colMap, 'quantity'));
        if ($quantity < 1) {
            $quantity = $this->extractQuantity($productText);
        }
        if ($quantity < 1) {
            $quantity = 1;
        }

        return [
            'source' => $source,
            'awb' => $awb,
            'phone_normalized' => $phone,
            'product_text' => $productText,
            'quantity' => $quantity,
            'address_norm' => $this->normalizeAddress($this->text($row, $colMap, 'address')),
            'raw_status' => $rawStatus,
            'status' => $status,
            'delivered_at' => $status === 'delivered'
                ? $this->parseDateTime($this->value($row, $colMap, 'delivered_date'), $source)
                : null,
        ];
    }

    /**
     * Mapping raw status dashboard → nilai aggregator_status (Inggris).
     *
     * Mapping kini dinamis dari tabel `tracking_status_rules` (dikelola admin
     * di halaman Aturan Status). Aturan bermasalah (FLIK 3PL "Problem...",
     * SPX OnHold reason, SICEPAT Bermasalah) juga didefinisikan di sana lewat
     * `problem_mode=required` — jadi raw status tak dikenal → null.
     *
     * @param  string|bool|null  $problemColumn  nilai kolom masalah file, atau
     *        `true` (kompatibilitas lama: paksa hasil 'problem').
     */
    public function mapStatus(string $source, string $rawStatus, string|bool|null $problemColumn = null): ?string
    {
        if ($problemColumn === true) {
            return 'problem';
        }

        return $this->statusRules->resolve($source, $rawStatus, $problemColumn === false ? null : $problemColumn);
    }

    protected function extractQuantity(string $productText): int
    {
        if (preg_match('/(\d+)\s*pcs/i', $productText, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    protected function normalizeAddress(?string $address): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $address)) ?? '');
    }

    // ─── File / header helpers ─────────────────────────────────

    /**
     * Baca semua baris dari file CSV/xlsx (PhpSpreadsheet), value diformat
     * sesuai sel aslinya agar tanggal & nomor HP tidak kehilangan format.
     */
    protected function readRows(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($ext, ['csv', 'txt', 'xlsx', 'xls'], true)) {
            throw new \RuntimeException('Format file tidak didukung: '.$ext);
        }

        return IOFactory::load($filePath)->getActiveSheet()->toArray(null, true, true, false);
    }

    /**
     * Deteksi sumber file beserta index baris header (header tak selalu di baris 1,
     * mis. SPX di baris 3). Scan 8 baris pertama.
     *
     * @return array{0:string,1:int} [source, headerIndex]
     */
    protected function detectSource(array $rows): array
    {
        foreach (array_slice($rows, 0, 8) as $i => $row) {
            $joined = implode(' ', $this->cleanHeaders($row));

            if (str_contains($joined, 'tracking status') && str_contains($joined, 'recipient phone')) {
                return ['spx', $i];
            }
            if (str_contains($joined, 'tracking no') && str_contains($joined, 'parcel value')) {
                return ['spx', $i];
            }
            if (str_contains($joined, 'nomor resi') && str_contains($joined, 'isi paket')) {
                return ['sicepat', $i];
            }
            if (str_contains($joined, 'nomor resi') && str_contains($joined, 'tanggal terkirim')) {
                return ['sicepat', $i];
            }
            if (str_contains($joined, 'order id') && str_contains($joined, 'awb')) {
                return ['flik', $i];
            }
            if (str_contains($joined, 'nama shopper') && str_contains($joined, 'status terakhir')) {
                return ['flik', $i];
            }
        }

        return ['spx', 0];
    }

    protected function cleanHeaders(array $row): array
    {
        return array_values(array_map(fn ($h) => $this->normalizeHeader($h), $row));
    }

    protected function normalizeHeader(?string $h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE/', '', $h ?? '');
        $h = strtolower(trim((string) $h));
        $h = preg_replace('/[^a-z0-9.]/u', ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);

        return trim($h);
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
            'phone' => ['no hp', 'no telp', 'recipient phone', 'telepon', 'phone', 'telp', 'hp'],
            'address' => ['recipient detail address', 'alamat lengkap', 'alamat penerima', 'alamat', 'address'],
            'product_name' => ['item name', 'item in parcel', 'isi paket', 'nama produk', 'produk', 'product'],
            'quantity' => ['no. of item', 'jumlah isi paket', 'jumlah', 'qty', 'quantity'],
            'status' => ['tracking status', 'status'],
            'problem' => ['status terakhir dari 3pl', 'delivery onhold reason', 'onhold reason'],
            'delivered_date' => ['delivered time', 'tanggal terkirim', 'terakhir update', 'delivered'],
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

    // ─── Value helpers ─────────────────────────────────────────

    protected function text(array $row, array $colMap, string $key): string
    {
        return $this->cellText($row[$colMap[$key] ?? -1] ?? '');
    }

    protected function value(array $row, array $colMap, string $key)
    {
        return $row[$colMap[$key] ?? -1] ?? '';
    }

    /**
     * Konversi nilai sel (bisa float dari xlsx) ke teks bersih.
     * `-` dianggap kosong; angka eksponensial (mis. 2.63E17) diperluas penuh.
     */
    protected function cellText($val): string
    {
        if ($val === null || $val === false) {
            return '';
        }

        if (is_float($val)) {
            $val = sprintf('%.0f', $val);
        }

        $val = trim((string) $val);
        if ($val === '-' || $val === '') {
            return '';
        }
        if (preg_match('/^[0-9.]+[eE][0-9]+$/', $val)) {
            $val = sprintf('%.0f', (float) $val);
        }

        return $val;
    }

    protected function decimal($val): float
    {
        $val = preg_replace('/[^0-9.,\-]/', '', (string) $val);

        return (float) $val;
    }

    /**
     * Parse datetime sesuai format sumber (urutan format per aggregator agar
     * d/m/Y (SICEPAT) tidak tertukar dengan m/d/Y (FLIK)).
     */
    protected function parseDateTime($val, string $source): ?string
    {
        $val = trim((string) $val);
        if ($val === '' || $val === '-') {
            return null;
        }

        $formats = match ($source) {
            'sicepat' => ['d/m/Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i:s', 'd-m-Y H:i', 'd/m/Y', 'd-m-Y'],
            'spx' => ['d-m-Y H:i:s', 'd-m-Y H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd-m-Y', 'd/m/Y'],
            default => ['m/d/Y H:i:s', 'm/d/Y H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'm/d/Y', 'd/m/Y'],
        };

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $val);
            if ($dt && (int) $dt->format('Y') >= 2000 && (int) $dt->format('Y') <= 2100) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        try {
            return (new \DateTime($val))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
