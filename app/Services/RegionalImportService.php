<?php

namespace App\Services;

use App\Models\Product;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RegionalImportService
{
    /**
     * Baca file Excel dan parse ke array rows.
     * Format kolom: province, payment_status, created_at (optional), product (optional).
     *
     * Kolom `product` (format "P.1 - Nama Produk - 22760") dipakai untuk mengetahui
     * fase iklan produk (running/testing berdasar timeline start_running):
     * baris running → tabel regional utama; baris testing → tabel regional
     * testing (DUAL FASE — sejak 18 Sep 2026 baris testing tidak dibuang lagi).
     */
    public function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows) || count($rows) < 2) {
            throw new \Exception('File Excel kosong atau tidak memiliki data.');
        }

        // Ambil header (row 1)
        $headers = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $rows[0]);

        // Cari index kolom yang dibutuhkan
        $colProvince = array_search('province', $headers);
        $colPaymentStatus = array_search('payment_status', $headers);
        $colCreatedAt = array_search('created_at', $headers);
        $colHandledBy = array_search('handled_by', $headers);

        // Kolom produk (opsional) — dipakai utk atribusi status iklan (running/testing)
        $colProduct = array_search('product', $headers);
        if ($colProduct === false) {
            foreach ($headers as $i => $h) {
                if ($h !== '' && (str_contains($h, 'product') || str_contains($h, 'produk'))) {
                    $colProduct = $i;

                    break;
                }
            }
        }

        if ($colProvince === false || $colPaymentStatus === false) {
            throw new \Exception(
                'Kolom wajib tidak ditemukan. Dibutuhkan: "province" dan "payment_status". '
                .'Kolom tersedia: '.implode(', ', $headers)
            );
        }

        // Matcher nama produk (sama dengan olah data halaman spending):
        // exact → contains → levenshtein. Hanya dipakai bila kolom product ada.
        $matcher = null;
        $productIndex = [];
        // Peta start_running per produk — klasifikasi status memakai TIMELINE produk
        // (produk running sejak tanggal berapa), bukan ad_status saat ini, agar
        // baris yang tanggalnya masih masa testing TETAP dianggap testing.
        $productRunningMap = [];
        if ($colProduct !== false) {
            $matcher = new ProductNameMatcher();
            $productIndex = $matcher->buildIndex();
            // pluck pada kolom berkast 'date' mengembalikan objek Carbon — WAJIB
            // dinormalisasi ke string Y-m-d. Tanpa ini `$tanggal >= $runningStart`
            // membandingkan string dgn 'Y-m-d 00:00:00': tanggal EXACT sama dgn
            // start_running dinilai lebih kecil (string lebih pendek = prefix) →
            // baris di hari produk MULAI running salah dikategorikan testing.
            $productRunningMap = Product::query()
                ->whereNotNull('start_running')
                ->pluck('start_running', 'id')
                ->map(fn ($v) => $v instanceof CarbonInterface ? $v->toDateString() : (string) $v)
                ->all();
        }

        $masterProvinces = config('regional.master_provinces', []);
        $provinceMapping = config('regional.province_mapping', []);

        $parsed = [];
        $errors = [];
        $phoneContacts = []; // data phone → CS mapping
        $skippedTesting = 0; // baris yang produknya ber-status iklan TESTING

        foreach ($rows as $idx => $row) {
            if ($idx === 0) {
                continue;
            } // skip header

            $provinceRaw = trim((string) ($row[$colProvince] ?? ''));
            $paymentStatus = trim((string) ($row[$colPaymentStatus] ?? ''));
            $createdAtRaw = $colCreatedAt !== false ? trim((string) ($row[$colCreatedAt] ?? '')) : '';

            if (empty($provinceRaw)) {
                continue;
            }

            // Kalibrasi nama provinsi
            $province = $this->calibrateProvince($provinceRaw, $provinceMapping, $masterProvinces);

            if ($province === 'UNKNOWN') {
                $errors[] = 'Baris '.($idx + 1).": Provinsi '{$provinceRaw}' tidak dikenal.";

                continue;
            }

            // Parse tanggal
            $tanggal = null;
            if (! empty($createdAtRaw)) {
                $tanggal = $this->parseDate($createdAtRaw);
            }

            // Fallback: jika kolom created_at tidak ada atau tidak valid,
            // gunakan tanggal hari ini (seperti fallback di Python)
            if (empty($tanggal)) {
                $tanggal = now()->format('Y-m-d');
            }

            $isPaid = strtolower($paymentStatus) === 'paid';

            // Ambil handled_by jika ada
            $handledBy = '';
            if ($colHandledBy !== false) {
                $handledBy = trim((string) ($row[$colHandledBy] ?? ''));
            }

            // ─── Atribusi produk (status iklan running/testing) ───
            // Nama produk dipecah 3 area dengan separator "-" (sama seperti
            // parseRegionalFile halaman spending): area 1 = kode teritorial,
            // area 2 = nama produk (dicocokkan ke products.name),
            // area 3 = kode whitelist (diabaikan di halaman ini).
            $productStatus = null;
            if ($colProduct !== false) {
                $rawProduct = trim((string) ($row[$colProduct] ?? ''));
                if ($rawProduct !== '') {
                    $parts = preg_split('/\s*-\s*/', $rawProduct);
                    $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

                    if (count($parts) >= 2) {
                        $nameParts = array_slice($parts, 1, -1);
                        $productName = trim(implode(' - ', $nameParts));
                        if ($productName === '') {
                            $productName = trim($parts[0]);
                        }

                        $prod = $matcher->match($productName, $productIndex);
                        if ($prod) {
                            $runningStart = $productRunningMap[$prod->id] ?? null;
                            $productStatus = ($runningStart && $tanggal >= $runningStart)
                                ? Product::AD_STATUS_RUNNING
                                : Product::AD_STATUS_TESTING;
                        }
                    }
                }
            }

            if ($productStatus === Product::AD_STATUS_TESTING) {
                $skippedTesting++;
            }

            $parsed[] = [
                'province' => $province,
                'tanggal' => $tanggal,
                'is_paid' => $isPaid,
                'handled_by' => $handledBy,
                'product_id' => null,
                'product_status' => $productStatus,
            ];

            // ─── Ekstrak phone → CS mapping dari file yang sama ───
            $phoneRaw = trim((string) ($row[4] ?? ''));
            $csName = trim((string) ($row[33] ?? ''));
            if (! empty($phoneRaw) && ! empty($csName)) {
                $phoneNormalized = self::normalizePhone($phoneRaw);
                if (! empty($phoneNormalized)) {
                    $phoneContacts[] = [
                        'phone_normalized' => $phoneNormalized,
                        'cs_name' => $csName,
                        'order_id' => trim((string) ($row[0] ?? '')),
                        'buyer_name' => trim((string) ($row[2] ?? '')),
                    ];
                }
            }
        }

        // Deduplikasi phone_contacts berdasarkan phone_normalized
        $uniquePhones = [];
        foreach ($phoneContacts as $pc) {
            $key = $pc['phone_normalized'];
            if (! isset($uniquePhones[$key])) {
                $uniquePhones[$key] = $pc;
            }
        }

        return [
            'data' => $parsed,
            'errors' => $errors,
            'total' => count($parsed),
            // Baris testing TIDAK dibuang lagi (DUAL FASE) — key ini kini
            // berarti "jumlah baris produk testing terdeteksi" (masuk tabel testing).
            'skipped_testing' => $skippedTesting,
            'phone_contacts' => array_values($uniquePhones),
        ];
    }

    /**
     * Agregasi data parsing untuk preview (tanpa simpan ke DB).
     *
     * DUAL FASE (18 September 2026): baris produk RUNNING (dan baris tanpa
     * atribusi produk — tak dikenal/tanpa kolom product, konservatif) masuk
     * grup running; baris produk TESTING TIDAK lagi dibuang — masuk grup
     * testing sendiri (tabel kedua di Detail Per Daerah).
     */
    public function previewData(array $parsedData): array
    {
        $isTesting = fn ($ps) => $ps === Product::AD_STATUS_TESTING;
        $isRunningSide = fn ($ps) => $ps !== Product::AD_STATUS_TESTING; // running ATAU null

        // ─── CS Stats per fase: lead/paid per CS per tanggal ───
        $csByDate = $this->groupCsByDate($parsedData, $isRunningSide);
        $csByDateTesting = $this->groupCsByDate($parsedData, $isTesting);

        // ─── Province grouping per fase ─────────
        [$byDate, $totalLead, $totalPaid, $totalRows] = $this->groupProvincesByDate($parsedData, $isRunningSide);
        [$byDateTesting, $totalTestingLead, $totalTestingPaid, $totalTestingRows] = $this->groupProvincesByDate($parsedData, $isTesting);

        // Baris testing TIDAK di-skip lagi — dihitung ke tabel testing.
        // Key lama dipertahankan (skipped_testing = jumlah baris testing terdeteksi).
        $skippedTesting = 0;
        foreach ($parsedData as $row) {
            if ($isTesting($row['product_status'] ?? null)) {
                $skippedTesting++;
            }
        }

        return [
            'by_date' => $byDate,
            'total_lead' => $totalLead,
            'total_paid' => $totalPaid,
            'total_rows' => $totalRows,
            'skipped_testing' => $skippedTesting,
            'cs_by_date' => $csByDate, // data CS stats per tanggal (running)
            // ─── Fase testing (baru) ───
            'by_date_testing' => $byDateTesting,
            'total_testing_lead' => $totalTestingLead,
            'total_testing_paid' => $totalTestingPaid,
            'total_testing_rows' => $totalTestingRows,
            'cs_by_date_testing' => $csByDateTesting,
        ];
    }

    /**
     * Group baris parsed per (tanggal, province) → by_date ter-sort.
     * $include menerima product_status baris (null = tanpa atribusi produk).
     *
     * @return array{0: array, 1: int, 2: int, 3: int} [byDate, totalLead, totalPaid, totalRows]
     */
    private function groupProvincesByDate(array $parsedData, \Closure $include): array
    {
        $grouped = [];
        foreach ($parsedData as $row) {
            if (! $include($row['product_status'] ?? null)) {
                continue;
            }

            $key = $row['tanggal'].'|'.$row['province'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'tanggal' => $row['tanggal'],
                    'province' => $row['province'],
                    'lead' => 0,
                    'paid' => 0,
                ];
            }
            $grouped[$key]['lead']++;
            if ($row['is_paid']) {
                $grouped[$key]['paid']++;
            }
        }

        // Group by tanggal
        $byDate = [];
        $totalLead = 0;
        $totalPaid = 0;

        foreach ($grouped as $item) {
            $tgl = $item['tanggal'];
            if (! isset($byDate[$tgl])) {
                $byDate[$tgl] = [];
            }
            $lead = $item['lead'];
            $paid = $item['paid'];
            $ratio = $lead > 0 ? round($paid / $lead * 100, 2) : 0;

            $byDate[$tgl][] = [
                'province' => $item['province'],
                'lead' => $lead,
                'paid' => $paid,
                'paid_ratio' => $ratio,
            ];
            $totalLead += $lead;
            $totalPaid += $paid;
        }

        // Sort dates ascending
        ksort($byDate);

        // Sort provinces within each date
        foreach ($byDate as &$items) {
            usort($items, fn ($a, $b) => strcmp($a['province'], $b['province']));
        }
        unset($items);

        return [$byDate, $totalLead, $totalPaid, count($grouped)];
    }

    /**
     * Group baris parsed per (tanggal, CS) untuk satu sisi fase → cs_by_date.
     */
    private function groupCsByDate(array $parsedData, \Closure $include): array
    {
        $csGrouped = [];
        foreach ($parsedData as $row) {
            $handledBy = $row['handled_by'] ?? '';
            if (empty($handledBy)) {
                continue;
            }
            if (! $include($row['product_status'] ?? null)) {
                continue;
            }

            $key = $row['tanggal'].'|'.$handledBy;
            if (! isset($csGrouped[$key])) {
                $csGrouped[$key] = [
                    'tanggal' => $row['tanggal'],
                    'cs_panggilan' => $handledBy,
                    'lead' => 0,
                    'paid' => 0,
                ];
            }
            $csGrouped[$key]['lead']++;
            if ($row['is_paid']) {
                $csGrouped[$key]['paid']++;
            }
        }

        // Group CS stats by date
        $csByDate = [];
        foreach ($csGrouped as $item) {
            $tgl = $item['tanggal'];
            if (! isset($csByDate[$tgl])) {
                $csByDate[$tgl] = [];
            }
            $csByDate[$tgl][] = $item;
        }
        ksort($csByDate);

        return $csByDate;
    }

    // ─── Private Helpers ───────────────────────────────────────

    /**
     * Kalibrasi nama provinsi sesuai mapping.
     */
    private function calibrateProvince(string $name, array $mapping, array $master): string
    {
        $name = strtoupper(trim($name));

        // Standarisasi SU MATERA → SU MATRA
        $name = str_replace('SUMATERA', 'SUMATRA', $name);

        // Cek mapping
        if (isset($mapping[$name])) {
            return $mapping[$name];
        }

        // Cek langsung ke master list
        if (in_array($name, $master)) {
            return $name;
        }

        return 'UNKNOWN';
    }

    /**
     * Parse tanggal dari string dengan berbagai format.
     * Mendukung format DD-MM-YYYY, DD/MM/YYYY, YYYY-MM-DD,
     * termasuk yang diikuti waktu seperti "01-07-2026 - 23:18".
     */
    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) {
            return null;
        }

        // Bersihkan string: hapus waktu/jam jika ada, sisakan tanggal saja
        $cleanDate = preg_replace('/[\s\-]+\d{2}[:.]\d{2}.*$/', '', $dateStr);
        $cleanDate = trim($cleanDate);

        // Coba format DD-MM-YYYY atau DD/MM/YYYY (dayfirst)
        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd.m.Y'];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $cleanDate);
            if ($dt && $dt->format($format) === $cleanDate) {
                return $dt->format('Y-m-d');
            }
        }

        // Fallback: biarkan PHP/timestamp mendeteksi
        try {
            $dt = new \DateTime($cleanDate);

            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
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
}
