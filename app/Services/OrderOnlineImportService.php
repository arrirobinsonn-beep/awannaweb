<?php

namespace App\Services;

use App\Models\OrderOnlineContact;
use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Impor data mentah order online (CSV dari toko) ke tabel `shipping_orders`.
 *
 * - 1 baris CSV = 1 order = 1 produk (tabel lebar + `raw_payload` untuk arsip).
 * - Kunci unik per batch: (order_online_import_batch_id, order_id).
 * - Provinsi dikalibrasi ke daftar master (config/regional.php).
 * - `handled_by` disimpan apa adanya; `handled_by_user_id` di-resolve batch.
 * - Couriers diisi otomatis dari tabel `courier_rules`.
 * - Sekaligus memperbarui `order_online_contacts` (mapping phone → CS).
 *
 * Status (CSV → DB):
 *  - processing             → real
 *  - pending + paid         → tembakan
 *  - pending + unpaid       → belum_diproses
 *  - cancelled              → cancel
 *  - completed              → TIDAK disimpan (di-skip saat parse)
 *
 * Hanya `real`/`tembakan` yang punya courier; status lain courier = null.
 *
 * Deteksi duplikat (phone_normalized + product_code + address sama, order_id
 * BERBEDA, ≤ 14 hari terakhir) berlaku untuk SEMUA status (termasuk real/
 * tembakan), dengan pengecualian payment_method `bank_transfer` yang selalu
 * dianggap repeat order (uang sudah diterima) dan TIDAK pernah jadi duplikat
 * (tetap menjadi source penanda untuk baris lain). Pembeda utamanya order_id:
 * - order_id BERUBAH + signature sama → `duplikat` (courier null, tak diexport)
 * - order_id SAMA (ke re-import) → masuk rule perbarui status / drop, BUKAN duplikat
 * - data cocok > 14 hari = repeat order (diproses normal)
 *
 * Re-import data yang sama (by order_id):
 * - Baris `real`/`tembakan` menghapus baris lama berstatus `belum_diproses`
 *   (order_id sama, di batch mana pun) lalu insert baru. Baris `duplikat`
 *   TIDAK dihapus — duplikat adalah order yang berbeda (order_id-nya sendiri),
 *   jadi tidak ikut "diperbarui" ketika order aslinya naik status.
 * - Jika `real`/`tembakan` dengan order_id sama SUDAH ada di batch lain → baris
 *   TIDAK di-insert (anti double-export, dihitung ke `double_real`); `cancel`
 *   dan `real` lama tidak dihapus/ditimpa.
 */
class OrderOnlineImportService
{
    /** Jumlah hari maksimal selisih agar order lama dianggap duplikat. */
    public const DUP_WINDOW_DAYS = 14;

    public function __construct(
        private readonly CourierRuleService $couriers = new CourierRuleService,
    ) {}

    /**
     * Baca & parse CSV menjadi baris ternormalisasi (belum disimpan).
     *
     * @return array{data: Collection, skips: array, total: int}
     */
    public function parse(string $filePath): array
    {
        $rows = $this->readCsv($filePath);
        if (empty($rows) || count($rows) < 2) {
            throw new \RuntimeException('File CSV kosong atau tidak memiliki baris data.');
        }

        $headers = $this->cleanHeaders($rows[0]);
        $colMap = $this->mapHeaders($headers);

        $data = collect();
        $skips = [];
        $seen = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $lineNo = $i + 2;
            $orderId = trim((string) $this->value($row, $colMap, 'order_id'));
            if ($orderId === '') {
                continue;
            }

            if (isset($seen[$orderId])) {
                continue;
            }
            $seen[$orderId] = true;

            if (strtolower($this->text($row, $colMap, 'status')) === 'completed') {
                $skips[] = "Baris {$lineNo}: status completed diabaikan";

                continue;
            }

            $parsed = $this->normalizeRow($row, $colMap);
            if ($parsed === null) {
                $skips[] = "Baris {$lineNo}: data tidak lengkap";

                continue;
            }

            $data->push($parsed);
        }

        return [
            'data' => $data,
            'skips' => $skips,
            'total' => $data->count(),
        ];
    }

    /**
     * Normalisasi satu baris CSV ke struktur shipping_orders.
     */
    protected function normalizeRow(array $row, array $colMap): ?array
    {
        $orderId = trim($this->text($row, $colMap, 'order_id'));
        if ($orderId === '') {
            return null;
        }

        $phone = $this->text($row, $colMap, 'phone');
        $phoneNormalized = self::normalizePhone($phone);

        $paymentMethod = strtolower($this->text($row, $colMap, 'payment_method'));
        $isCod = $paymentMethod === 'cod';
        $amount = $this->decimal($this->value($row, $colMap, 'gross_revenue'));
        if ($amount <= 0) {
            $amount = $this->decimal($this->value($row, $colMap, 'product_price'));
        }
        $shippingCost = $this->decimal($this->value($row, $colMap, 'shipping_cost'));
        $provinceRaw = $this->text($row, $colMap, 'province');
        $province = $this->calibrateProvince($provinceRaw);

        $status = strtolower($this->text($row, $colMap, 'status'));
        $paymentStatus = strtolower($this->text($row, $colMap, 'payment_status'));

        $mappedStatus = match (true) {
            $status === 'processing' => 'real',
            $status === 'pending' && $paymentStatus === 'paid' => 'tembakan',
            $status === 'cancelled' => 'cancel',
            $status === 'pending' => 'belum_diproses',
            default => 'belum_diproses',
        };

        $productRaw = $this->text($row, $colMap, 'product');
        $productName = $productRaw;
        $metaAccount = null;
        $separator = strrpos($productRaw, ' - ');
        if ($separator !== false) {
            $productName = trim(substr($productRaw, 0, $separator));
            $metaAccount = trim(substr($productRaw, $separator + 3));
        }

        $quantity = max(1, (int) $this->decimal($this->value($row, $colMap, 'quantity')) ?: 1);
        $variation = $this->text($row, $colMap, 'variation');
        if (preg_match('/Dapat\s*(\d+)/i', $variation, $m)) {
            $quantity = max(1, (int) $m[1]);
            $productName = trim($productName.' '.$quantity.' pcs');
        }

        return [
            'order_id' => $orderId,
            'awb' => $this->text($row, $colMap, 'receipt_number'),
            'customer_name' => $this->text($row, $colMap, 'name'),
            'phone' => $phone,
            'phone_normalized' => $phoneNormalized,
            'address' => $this->text($row, $colMap, 'address'),
            'province' => $province,
            'city' => $this->text($row, $colMap, 'city'),
            'subdistrict' => $this->text($row, $colMap, 'subdistrict'),
            'postal_code' => $this->text($row, $colMap, 'zip'),
            'payment_method' => $paymentMethod,
            'status' => $mappedStatus,
            'handled_by' => $this->text($row, $colMap, 'handled_by'),
            'product_name' => $productName,
            'meta_account' => $metaAccount,
            'product_code' => $this->text($row, $colMap, 'product_code'),
            'variation' => $variation,
            'product_id' => null,
            'product_variant_id' => null,
            'quantity' => $quantity,
            'weight' => $this->decimal($this->value($row, $colMap, 'weight')),
            'amount' => $amount,
            'is_cod' => $isCod,
            'shipping_cost' => $shippingCost,
            'raw_payload' => $this->buildRawPayload($row, $colMap),
        ];
    }

    /**
     * Preview: statistik impor tanpa menyimpan (untuk modal preview).
     *
     * @return array{total:int, sample:Collection, skips:array, unknown_cs:array}
     */
    public function preview(string $filePath): array
    {
        $parsed = $this->parse($filePath);
        $rows = $parsed['data'];

        $csIndex = $this->userIndexByNama();
        $unknownCs = [];
        foreach ($rows as $row) {
            $name = trim((string) $row['handled_by']);
            if ($name === '') {
                continue;
            }
            if (! isset($csIndex[$this->normalizeName($name)])) {
                $unknownCs[] = $name;
            }
        }

        return [
            'total' => $parsed['total'],
            'sample' => $rows->take(5)->values(),
            'skips' => $parsed['skips'],
            'unknown_cs' => array_values(array_unique($unknownCs)),
        ];
    }

    /**
     * Simpan seluruh baris ke database dalam satu batch (transaksi).
     *
     * @return array{inserted:int, updated:int, skipped:int, duplicates:int, unknown_cs:array, deleted:int, double_real:int}
     */
    public function import(string $filePath, string $sender = '', ?string $originalFilename = null): array
    {
        $parsed = $this->parse($filePath);
        $rows = $parsed['data'];

        if ($rows->isEmpty()) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => count($parsed['skips']), 'duplicates' => 0, 'unknown_cs' => [], 'deleted' => 0, 'double_real' => 0];
        }

        return DB::transaction(function () use ($rows, $filePath, $parsed, $sender, $originalFilename) {
            $batch = OrderOnlineImportBatch::create([
                // Nama file asli dari user (bukan nama hash hasil store()); fallback ke basename path.
                'original_filename' => $originalFilename ?: basename($filePath),
                'stored_path' => $filePath,
                'sender' => $sender,
                'status' => 'processing',
                'total_rows' => $rows->count(),
            ]);

            $csIndex = $this->userIndexByNama();
            $unknownCs = [];

            $existing = ShippingOrder::where('order_online_import_batch_id', $batch->id)
                ->whereIn('order_id', $rows->pluck('order_id')->all())
                ->get()
                ->keyBy('order_id');

            $byOrderId = ShippingOrder::whereIn('order_id', $rows->pluck('order_id')->all())
                ->get()
                ->groupBy('order_id');

            $productMap = Product::whereIn('code', $rows->pluck('product_code')->filter()->unique()->all())
                ->get(['id', 'code'])
                ->pluck('id', 'code');

            $variantIndex = ProductVariant::whereIn('product_id', $productMap->values())
                ->get()
                ->groupBy('product_id');

            $dupSignatures = $this->loadDuplicateSignatures($rows);

            $inserted = 0;
            $updated = 0;
            $duplicates = 0;
            $deleted = 0;
            $doubleReal = 0;

            foreach ($rows as $row) {
                $row['order_online_import_batch_id'] = $batch->id;

                $signature = $this->orderSignature(
                    $row['phone_normalized'] ?? null,
                    $row['product_code'] ?? null,
                    $row['address'] ?? null,
                );
                if ($signature !== '' && $row['phone_normalized'] !== '') {
                    $matchedIds = $dupSignatures[$signature] ?? [];
                    $otherIds = array_keys(array_diff_key($matchedIds, [$row['order_id'] => true]));
                    $isBankTransfer = strtolower((string) ($row['payment_method'] ?? '')) === 'bank_transfer';

                    if ($otherIds !== [] && ! $isBankTransfer) {
                        $row['status'] = 'duplikat';
                        $duplicates++;
                    }
                    $dupSignatures[$signature][$row['order_id']] = true;
                }

                if (in_array($row['status'], ShippingOrder::EXPORTABLE_STATUSES, true)) {
                    $same = $byOrderId[$row['order_id']] ?? collect();

                    // Hanya baris lama berstatus `belum_diproses` yang merupakan
                    // order yang SAMA (order_id sama) → aman dihapus saat naik status.
                    // Baris `duplikat` dibiarkan utuh (order duplikat berbeda order).
                    $stale = $same->where('status', 'belum_diproses');
                    if ($stale->isNotEmpty()) {
                        ShippingOrder::whereKey($stale->pluck('id'))->delete();
                        $deleted += $stale->count();
                    }

                    if ($same->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)->isNotEmpty()) {
                        $doubleReal++;

                        continue;
                    }
                }

                if (! in_array($row['status'], ShippingOrder::EXPORTABLE_STATUSES, true)) {
                    $row['courier'] = null;
                } elseif ($row['status'] === 'tembakan') {
                    $row['courier'] = 'spx';
                } else {
                    $row['courier'] = $this->couriers->resolve($row['payment_method'], $row['province'], $row['product_code'] ?? null);
                }

                $row['product_id'] = $productMap[$row['product_code'] ?? ''] ?? null;
                $row['product_variant_id'] = $row['product_id'] !== null
                    ? $this->resolveVariant($row['product_id'], $row['variation'] ?? '', $variantIndex[$row['product_id']] ?? collect())
                    : null;

                $variant = $row['product_variant_id'] !== null
                    ? ($variantIndex[$row['product_id']] ?? collect())->firstWhere('id', $row['product_variant_id'])
                    : null;
                if ($variant) {
                    $row['product_code'] = $variant->code;
                }

                $handledBy = trim((string) $row['handled_by']);
                $row['handled_by_user_id'] = null;
                if ($handledBy !== '') {
                    $user = $csIndex[$this->normalizeName($handledBy)] ?? null;
                    if ($user) {
                        $row['handled_by_user_id'] = $user->id;
                    } else {
                        $unknownCs[] = $handledBy;
                    }
                }

                $has = $existing->get($row['order_id']);
                if ($has) {
                    $has->update($row);
                    $updated++;
                } else {
                    ShippingOrder::create($row);
                    $inserted++;
                }
            }

            $batch->update([
                'status' => 'completed',
                'processed_rows' => $rows->count(),
                'success_rows' => $inserted + $updated + $duplicates,
                'failed_rows' => $doubleReal,
            ]);

            $this->syncOrderOnlineContacts($rows);

            return [
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => count($parsed['skips']),
                'duplicates' => $duplicates,
                'unknown_cs' => array_values(array_unique($unknownCs)),
                'deleted' => $deleted,
                'double_real' => $doubleReal,
            ];
        });
    }

    /**
     * Ambil semua signature duplikat dari DB (≤ DUP_WINDOW_DAYS) berikut order_id
     * asalnya, dari SEMUA status (real/tembakan/belum_diproses/duplikat/dst), agar
     * baris `real`/`tembakan` pun ikut dicek. 1 query batch `whereIn`.
     *
     * @return array<string, array<string, true>> signature → [order_id => true]
     */
    protected function loadDuplicateSignatures(Collection $rows): array
    {
        $phones = $rows
            ->pluck('phone_normalized')
            ->filter()
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return [];
        }

        $signatures = [];
        ShippingOrder::whereIn('phone_normalized', $phones)
            ->where('created_at', '>=', now()->subDays(self::DUP_WINDOW_DAYS))
            ->select(['phone_normalized', 'product_code', 'address', 'order_id'])
            ->get()
            ->each(function ($o) use (&$signatures) {
                $signatures[$this->orderSignature($o->phone_normalized, $o->product_code, $o->address)][$o->order_id] = true;
            });

        return $signatures;
    }

    protected function orderSignature(?string $phone, ?string $productCode, ?string $address): string
    {
        $productCode = explode('+', trim((string) $productCode))[0];

        return mb_strtolower(trim((string) $phone).'|'.trim($productCode).'|'.$this->normalizeAddress($address));
    }

    /**
     * Pilih varian produk untuk order online berdasarkan teks variation (CSV).
     * - Power diekstrak dari pola "Plus +1.00" / "Plus 1.25" di kolom variation.
     * - Cocok ke varian dengan power sama (float), fallback ke varian default
     *   (aktif pertama urutan power terkecil) agar order tetap bisa diekspor.
     *
     * @param  Collection<int, ProductVariant>  $variants
     */
    protected function resolveVariant(int $productId, string $variation, Collection $variants): ?int
    {
        if ($variants->isEmpty()) {
            return null;
        }

        // 1. Coba cocokkan power (kacamata: "Plus +1.50")
        $power = $this->extractPower($variation);
        if ($power !== null) {
            foreach ($variants as $variant) {
                if ((float) $variant->power === $power) {
                    return $variant->id;
                }
            }
        }

        // 2. Coba cocokkan berdasarkan nama/kode varian (non-kacamata:
        //    "Motif: Bunga", "Warna: Merah", "Bunga", dll.)
        $keyword = $this->extractVariantKeyword($variation);
        if ($keyword !== '') {
            $match = $this->matchVariantByKeyword($variants, $keyword);
            if ($match !== null) {
                return $match;
            }
        }

        // 3. Fallback ke varian default (power terkecil)
        $default = $variants
            ->where('status', 'active')
            ->sortBy(fn ($v) => [(float) $v->power, $v->id])
            ->first();

        return $default?->id;
    }

    /**
     * Ekstrak nilai power dari teks variation, mis. "Plus +1.50" → 1.5.
     */
    protected function extractPower(string $variation): ?float
    {
        if ($variation === '') {
            return null;
        }

        if (preg_match('/Plus\s*([+\-]?\d+(?:\.\d+)?)/i', $variation, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    /**
     * Ekstrak kata kunci varian dari teks variasi (non-power).
     * Contoh:
     *  - "Motif: Bunga" → "bunga"
     *  - "Warna: Merah, Motif: Kartun" → "merah" (token pertama)
     *  - "Bunga" → "bunga"
     *  - "" → ""
     */
    protected function extractVariantKeyword(string $variation): string
    {
        $variation = trim($variation);
        if ($variation === '') {
            return '';
        }

        // Buang prefix "Ukuran:" / "Size:" karena itu untuk power/kacamata
        $variation = preg_replace('/^(Ukuran|Size)\s*:\s*/i', '', $variation);

        // Ambil token pertama (pisah koma/semikolon)
        $parts = preg_split('/[,;\-]/', $variation);
        $first = trim($parts[0] ?? '');
        if ($first === '') {
            return '';
        }

        // Bila ada "Key: Value", ambil Value-nya
        if (preg_match('/^\w+\s*:\s*(.+)$/i', $first, $m)) {
            $first = trim($m[1]);
        }

        return mb_strtolower($first);
    }

    /**
     * Cocokkan keyword varian terhadap koleksi varian produk.
     * Prioritas: exact → contains (keyword dalam nama) → contains (nama dalam keyword).
     */
    protected function matchVariantByKeyword(Collection $variants, string $keyword): ?int
    {
        $active = $variants->where('status', 'active');
        if ($active->isEmpty()) {
            return null;
        }

        // Exact match (nama atau kode)
        foreach ($active as $variant) {
            if (mb_strtolower(trim((string) $variant->name)) === $keyword
                || mb_strtolower(trim((string) $variant->code)) === $keyword) {
                return $variant->id;
            }
        }

        // Keyword mengandung nama varian (mis. "bunga kartun" mengandung "bunga")
        foreach ($active as $variant) {
            $name = mb_strtolower(trim((string) $variant->name));
            if ($name !== '' && mb_strpos($keyword, $name) !== false) {
                return $variant->id;
            }
        }

        // Nama varian mengandung keyword (mis. varian "Bunga" cocok "bunga")
        foreach ($active as $variant) {
            $name = mb_strtolower(trim((string) $variant->name));
            if ($name !== '' && mb_strpos($name, $keyword) !== false) {
                return $variant->id;
            }
        }

        return null;
    }

    protected function normalizeAddress(?string $address): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $address)) ?? '');
    }

    /**
     * Perbarui order_online_contacts (phone → CS) dari baris yang sama.
     * Dedupe by phone_normalized (pola regional upload).
     */
    protected function syncOrderOnlineContacts(Collection $rows): void
    {
        $unique = [];
        foreach ($rows as $row) {
            $phone = $row['phone_normalized'] ?? '';
            if ($phone === '') {
                continue;
            }
            $unique[$phone] = [
                'phone_normalized' => $phone,
                'cs_name' => $row['handled_by'] ?? '',
                'order_id' => $row['order_id'] ?? '',
                'buyer_name' => $row['customer_name'] ?? '',
            ];
        }

        foreach ($unique as $contact) {
            OrderOnlineContact::updateOrCreate(
                ['phone_normalized' => $contact['phone_normalized']],
                $contact,
            );
        }
    }

    // ─── Helpers ───────────────────────────────────────────────

    protected function buildRawPayload(array $row, array $colMap): array
    {
        $payload = [];
        foreach ($colMap as $key => $idx) {
            $payload[$key] = $row[$idx] ?? '';
        }

        return $payload;
    }

    protected function userIndexByNama(): array
    {
        $index = [];
        foreach (User::query()->get(['id', 'nama', 'panggilan']) as $user) {
            if ($user->nama) {
                $index[$this->normalizeName($user->nama)] = $user;
            }
            if ($user->panggilan) {
                $index[$this->normalizeName($user->panggilan)] = $user;
            }
        }

        return $index;
    }

    protected function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 8) {
            return '';
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        return $phone;
    }

    protected function calibrateProvince(string $name): string
    {
        $name = strtoupper(trim($name));
        if ($name === '') {
            return '';
        }
        $name = str_replace('SUMATERA', 'SUMATRA', $name);

        $mapping = config('regional.province_mapping', []);
        $master = config('regional.master_provinces', []);

        if (isset($mapping[$name])) {
            return $mapping[$name];
        }

        return in_array($name, $master) ? $name : $name;
    }

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
        $h = preg_replace('/[^a-z0-9.]/u', '_', $h);
        $h = preg_replace('/_+/', '_', $h);

        return trim($h, '_');
    }

    protected function mapHeaders(array $headers): array
    {
        $keys = [
            'order_id', 'product', 'name', 'email', 'phone', 'address', 'province', 'city',
            'subdistrict', 'zip', 'status', 'payment_status', 'payment_method', 'payment_info',
            'product_price', 'cogs', 'discount', 'quantity', 'bump', 'bump_price', 'notes',
            'courier', 'shipping_cost', 'cod_cost', 'shipping_markup', 'receipt_number', 'other_cost',
            'gross_revenue', 'net_revenue', 'created_at', 'processing_at', 'completed_at', 'paid_at',
            'handled_by', 'coupon', 'product_code', 'utm_campaign', 'utm_medium', 'utm_source',
            'utm_content', 'utm_term', 'tags', 'dropshipper_name', 'dropshipper_phone', 'variation',
            'order_type', 'reseller_name', 'weight', 'original_shipping_cost', 'ip_address',
            'variation_code', 'shipping_method',
        ];

        $result = [];
        foreach ($keys as $key) {
            $idx = array_search($key, $headers);
            if ($idx !== false) {
                $result[$key] = $idx;
            }
        }

        return $result;
    }

    protected function value(array $row, array $colMap, string $key)
    {
        return $row[$colMap[$key] ?? -1] ?? '';
    }

    protected function text(array $row, array $colMap, string $key): string
    {
        return trim((string) ($row[$colMap[$key] ?? -1] ?? ''));
    }

    protected function decimal($val): float
    {
        $val = str_replace(['.', ','], ['', ','], (string) $val);
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $val);
    }
}
