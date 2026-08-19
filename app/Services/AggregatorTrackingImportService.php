<?php

namespace App\Services;

use App\Models\ShippingOrder;
use App\Models\TrackingHeaderMapping;
use App\Models\TrackingSourceConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Mengisi kolom tracking `shipping_orders` (awb, aggregator_status, delivered_at)
 * dari file dashboard aggregator (FLIK / SiCepat / SPX) yang di-upload admin.
 *
 * Baris file dihubungkan ke order lewat signature:
 * Pencocokan hanya pakai 2 kolom: phone_normalized + customer_name
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

    /** @var array<string, array<string, string>> header normal → db_column per source (cache per instance) */
    private array $headerMapCache = [];

    /** @var array<string, string> source → phone_format (cache per instance) */
    private array $phoneFormatCache = [];

    /**
     * Baca & normalisasi file dashboard menjadi baris tracking (belum disimpan).
     *
     * @return array{source:?string, data:Collection, skips:array, total:int}
     */
    public function parse(string $filePath, ?string $source = null): array
    {
        $rows = $this->readRows($filePath);
        if (empty($rows)) {
            throw new \RuntimeException('File kosong atau tidak memiliki baris.');
        }

        if ($source !== null) {
            $headerIndex = 0;
            foreach (array_slice($rows, 0, 8) as $i => $row) {
                if (count(array_filter($row)) >= 3) {
                    $headerIndex = $i;
                    break;
                }
            }
        } else {
            [$source, $headerIndex] = $this->detectSource($rows);
        }
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
     * Ekstrak daftar HEADER unik dari file dashboard (halaman Aturan Status —
     * pola upload-mapping seperti export template, tapi UI dibalik: kolom kiri
     * = kolom DATABASE (tetap, teks), kolom kanan = pilih header CSV dari file).
     * Mapping lama ikut terbawa per kolom database (db_column => header).
     *
     * @return array{source:?string, headers: array<int,string>, mapping: array<string,string>}
     */
    public function extractHeaders(string $filePath, ?string $source = null): array
    {
        $rows = $this->readRows($filePath);
        if (empty($rows)) {
            throw new \RuntimeException('File kosong atau tidak memiliki baris.');
        }

        if ($source !== null) {
            $headerIndex = 0;
            foreach (array_slice($rows, 0, 8) as $i => $row) {
                if (count(array_filter($row)) >= 3) {
                    $headerIndex = $i;
                    break;
                }
            }
        } else {
            [$source, $headerIndex] = $this->detectSource($rows);
        }
        $headers = $this->cleanHeaders($rows[$headerIndex]);
        $existing = $this->headerMappingFor($source); // header => db_column

        // Balik: db_column => header (carry-over untuk UI yang dibalik)
        $mapping = [];
        foreach ($existing as $header => $column) {
            if (! isset($mapping[$column]) && in_array($header, $headers, true)) {
                $mapping[$column] = $header;
            }
        }

        return ['source' => $source, 'headers' => array_values($headers), 'mapping' => $mapping];
    }

    /**
     * Simpan mapping header CSV → kolom database untuk satu sumber (bulk
     * replace: hapus semua mapping lama sumber itu, lalu buat dari items).
     *
     * Items datang dari UI yang dibalik: tiap item = {db_column, header}.
     * Satu header hanya boleh dipakai untuk SATU kolom database (unique
     * `(source, header)` di DB) — dipakai dua kali → RuntimeException.
     *
     * @param  array<int, array{db_column:string, header:string}>  $items
     */
    public function saveHeaderMapping(string $source, array $items): int
    {
        $source = strtolower(trim($source));
        $count = 0;

        DB::transaction(function () use ($source, $items, &$count) {
            TrackingHeaderMapping::where('source', $source)->delete();

            $seenHeaders = [];
            foreach ($items as $item) {
                $column = trim((string) ($item['db_column'] ?? ''));
                $header = strtolower(trim((string) ($item['header'] ?? '')));
                if ($column === '' || $header === '') {
                    continue;
                }
                if (isset($seenHeaders[$header])) {
                    throw new \RuntimeException("Header \"{$header}\" dipakai untuk lebih dari satu kolom database.");
                }
                $seenHeaders[$header] = true;

                TrackingHeaderMapping::create([
                    'source' => $source,
                    'header' => $header,
                    'db_column' => $column,
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Proses satu file di dalam satu transaksi: cocokkan tiap baris ke
     * shipping_orders dan isi awb / aggregator_status / delivered_at.
     *
     * @return array{source:?string,total:int,matched:int,updated:int,stock_returned:int,unmatched:array,ambiguous:array}
     */
    public function import(string $filePath, ?string $source = null): array
    {
        $parsed = $this->parse($filePath, $source);
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
                $resolved = $this->resolveOrder($candidates->get($row['phone_normalized'], collect()), $row);

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
     * Cari 1 order yang cocok dengan baris tracking.
     *
     * Pencocokan hanya berdasarkan 2 kolom:
     * - `phone_normalized` (sudah di-filter di batch query)
     * - `customer_name` (dari kolom "Nama Penerima" / "Customer Name" file)
     *
     * @param  Collection<int, ShippingOrder>  $candidates  kandidat dengan phone SAMA
     * @return array{order:?ShippingOrder,ambiguous:bool}
     */
    protected function resolveOrder(Collection $candidates, array $row): array
    {
        $nameNorm = trim((string) ($row['name_norm'] ?? ''));
        if ($nameNorm === '') {
            // Nama kosong di file → tidak bisa match
            return ['order' => null, 'ambiguous' => false];
        }

        $byName = $candidates->filter(
            fn ($o) => $this->normalizeName($o->customer_name) === $nameNorm
        );

        if ($byName->count() === 1) {
            return ['order' => $byName->first(), 'ambiguous' => false];
        }
        if ($byName->count() > 1) {
            return ['order' => null, 'ambiguous' => true];
        }

        // Nama tidak cocok dengan siapa pun
        return ['order' => null, 'ambiguous' => false];
    }

    /**
     * Normalisasi satu baris dashboard menjadi struktur tracking.
     */
    protected function normalizeRow(array $row, array $colMap, string $source): ?array
    {
        $awb = trim($this->text($row, $colMap, 'tracking_number'));
        $phone = $this->normalizePhoneFor($source, $this->text($row, $colMap, 'phone'));
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
            'customer_name' => $this->text($row, $colMap, 'customer_name'),
            'name_norm' => $this->normalizeName($this->text($row, $colMap, 'customer_name')),
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
        // Pola promo "Dapat N" (Beli 1 Dapat 2, PAKET 1 DAPAT 9 PCS) — qty terjual
        // = angka setelah "Dapat" (konsisten dgn OrderOnlineImportService).
        if (preg_match('/dapat\s*(\d+)/i', $productText, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(\d+)\s*pcs/i', $productText, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    protected function normalizeAddress(?string $address): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $address)) ?? '');
    }

    protected function normalizeName(?string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $name)) ?? '');
    }

    /**
     * Normalisasi nomor HP file dashboard → format 62 (cocok dgn DB).
     *
     * Format dikonfigurasi per dashboard di tabel `tracking_source_configs`
     * (opsi "Format No HP di File" di halaman Aturan Status):
     *
     *   - auto (default): `OrderOnlineImportService::normalizePhone` — 0/8/62 → 62
     *   - 8  : nomor file berawalan 8 (SPX) → tambah 62 di depan
     *   - 0  : nomor file berawalan 0 → ganti dengan 62
     *   - 62 : nomor file sudah berawalan 62 → dipakai apa adanya
     *
     * Semua arah tetap menghasilkan awalan 62 agar merge dengan
     * `phone_normalized` berhasil.
     */
    protected function normalizePhoneFor(string $source, string $raw): string
    {
        $phone = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($phone) < 8) {
            return '';
        }

        $format = $this->phoneFormatFor($source);

        return match ($format) {
            '8' => str_starts_with($phone, '0')
                ? '62'.substr($phone, 1)
                : (str_starts_with($phone, '62') ? $phone : '62'.$phone),
            '0' => str_starts_with($phone, '62')
                ? $phone
                : '62'.ltrim($phone, '0'),
            '62' => $phone,
            default => OrderOnlineImportService::normalizePhone($phone),
        };
    }

    /**
     * Format No HP untuk satu sumber (dari DB, cache per instance — anti N+1).
     */
    protected function phoneFormatFor(string $source): string
    {
        if (! isset($this->phoneFormatCache[$source])) {
            $this->phoneFormatCache[$source] = TrackingSourceConfig::where('source', $source)
                ->value('phone_format') ?? 'auto';
        }

        return $this->phoneFormatCache[$source];
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
        // Deteksi 100% dari DB (tracking_header_mappings): hitung berapa header
        // yang ter-map per source muncul di baris CSV → source terbanyak menang.
        $sourceHeaders = \App\Models\TrackingHeaderMapping::query()
            ->select('source', 'header')
            ->get()
            ->groupBy('source')
            ->map(fn ($g) => $g->pluck('header')->map(fn ($h) => $this->normalizeHeader($h))->all())
            ->all();

        if ($sourceHeaders === []) {
            throw new \RuntimeException(
                'Belum ada mapping header untuk source manapun. '
                .'Buka halaman Aturan Status → pilih dashboard → upload file CSV.'
            );
        }

        foreach (array_slice($rows, 0, 8) as $i => $row) {
            $cleaned = $this->cleanHeaders($row);
            $best = ['source' => null, 'hits' => 0];
            foreach ($sourceHeaders as $src => $headers) {
                $hits = count(array_intersect($cleaned, $headers));
                if ($hits > $best['hits']) {
                    $best = ['source' => $src, 'hits' => $hits];
                }
            }
            if ($best['source'] !== null && $best['hits'] >= 2) {
                return [$best['source'], $i];
            }
        }

        throw new \RuntimeException(
            'Sumber file tidak dikenali. Pastikan file memiliki minimal 2 header '
            .'yang sama dengan mapping di halaman Aturan Status.'
        );
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

    /**
     * Mapping header CSV → kolom database BAWAAN dari file template dashboard
     * (dipakai TrackingHeaderMappingSeeder — training/templateTracking).
     *
     * Membaca header file → mencocokkan tiap header ke kolom DB via alias
     * bawaan (TANPA melihat mapping DB) sehingga seeder menghasilkan default
     * yang konsisten dengan logika import.
     *
     * @return array{source:?string, mapping: array<string,string>}  header → db_column
     */
    public function extractDefaultMapping(string $filePath, ?string $source = null): array
    {
        $rows = $this->readRows($filePath);
        if (empty($rows)) {
            throw new \RuntimeException('File kosong atau tidak memiliki baris.');
        }

        if ($source === null) {
            [$source, $headerIndex] = $this->detectSource($rows);
        } else {
            // Cari baris header otomatis — cari baris yang punya minimal 3 non-kosong
            $headerIndex = 0;
            foreach (array_slice($rows, 0, 8) as $i => $row) {
                if (count(array_filter($row)) >= 3) {
                    $headerIndex = $i;
                    break;
                }
            }
        }
        $headers = $this->cleanHeaders($rows[$headerIndex]);
        $colMap = $this->mapHeaders($headers, $source, []); // murni alias, tanpa DB

        $mapping = [];
        foreach ($colMap as $column => $index) {
            $mapping[$headers[$index]] = $column;
        }

        return ['source' => $source, 'mapping' => $mapping];
    }

    /**
     * Mapping header → kunci kolom internal (tracking_number, phone, ...).
     *
     * Mapping DB (`tracking_header_mappings`, dikelola admin per dashboard)
     * MENANG atas alias hardcoded untuk header yang sama; header lain tetap
     * dicocokkan via alias (fallback) — jadi mapping sebagian tidak memutus
     * kolom lain. $dbMap boleh di-override (mis. [] utk murni alias di seeder).
     */
    protected function mapHeaders(array $headers, string $source, ?array $dbMap = null): array
    {
        $dbMap ??= $this->headerMappingFor($source);
        $aliases = $this->aliasMap($source);

        $result = [];
        foreach ($headers as $i => $lower) {
            if (isset($dbMap[$lower]) && $dbMap[$lower] !== '') {
                $result[$dbMap[$lower]] = $i;

                continue;
            }

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

    /**
     * Mapping header normal → db_column untuk satu sumber (dari DB, cache
     * per instance — anti N+1).
     *
     * @return array<string, string>
     */
    protected function headerMappingFor(string $source): array
    {
        if (! isset($this->headerMapCache[$source])) {
            $this->headerMapCache[$source] = TrackingHeaderMapping::where('source', $source)
                ->get()
                ->pluck('db_column', 'header')
                ->all();
        }

        return $this->headerMapCache[$source];
    }

    protected function aliasMap(string $source): array
    {
        $common = [
            'phone' => ['no hp', 'no telp', 'recipient phone', 'telepon', 'phone', 'telp', 'hp'],
            'customer_name' => ['nama shopper', 'recipient name', 'nama penerima', 'customer name', 'buyer name', 'nama pelanggan'],
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
