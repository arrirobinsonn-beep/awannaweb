<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KirimanImportService
{
    public function parseExcel(string $filePath, bool $autoCreateProducts = false): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows) || count($rows) < 2) {
            throw new \Exception('File Excel kosong atau tidak memiliki data.');
        }

        $allProducts = Product::aktif()->get();
        $format = $this->detectFormat($rows);

        $result = match ($format) {
            'flik' => $this->parseFlik($rows, $allProducts, $autoCreateProducts),
            'spx' => $this->parseSpx($rows, $allProducts, $autoCreateProducts),
            'sicepat' => $this->parseSicepat($rows, $allProducts, $autoCreateProducts),
            default => throw new \Exception('Format file tidak dikenali. Gunakan file dari FLIK, SPX, atau SICEPAT.'),
        };

        if ($result['total'] === 0 && ! empty($rows)) {
            $firstRows = array_slice($rows, 0, min(5, count($rows)));
            Log::warning('[KirimanImport] total=0 | format='.$format, [
                'first_rows' => $firstRows,
            ]);
        }

        if ($autoCreateProducts) {
            $this->resolveMissingProducts($result['data'], $result['groups'], $result['matched_products']);
        }

        return $result;
    }

    // ─── Format detection ───────────────────────────────────────

    private function detectFormat(array $rows): string
    {
        $joined = '';
        for ($ri = 0; $ri <= min(1, count($rows) - 1); $ri++) {
            $row = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[$ri]);
            $joined .= ' ' . implode(' ', $row);
        }

        // SPX: headers row 1 has "tracking no" + "parcel value" or row 0 has "report download time"
        if (str_contains($joined, 'tracking no') && str_contains($joined, 'parcel value')) {
            return 'spx';
        }
        if (str_contains($joined, 'report download time')) {
            return 'spx';
        }
        if (str_contains($joined, 'tracking no') && str_contains($joined, 'create time') && str_contains($joined, 'recipient phone')) {
            return 'spx';
        }

        // SICEPAT: has "nomor resi" AND "isi paket"
        if (str_contains($joined, 'nomor resi') && str_contains($joined, 'isi paket')) {
            return 'sicepat';
        }

        // FLIK: has "order id" + "awb" (more specific than just "order id")
        if (str_contains($joined, 'order id') && str_contains($joined, 'awb')) {
            return 'flik';
        }
        if (str_contains($joined, 'order id') && str_contains($joined, 'tanggal pembuatan')) {
            return 'flik';
        }
        if (str_contains($joined, 'sumber') && str_contains($joined, 'nama produk')) {
            return 'flik';
        }

        // Fallback
        return 'flik';
    }

    // ─── FLIK parser ────────────────────────────────────────────

    private function parseFlik(array $rows, Collection $allProducts, bool $autoCreateProducts = false): array
    {
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = $this->mapHeaders($headers);

        $parsed = [];
        $errors = [];
        $matchedIds = [];

        foreach ($rows as $idx => $row) {
            if ($idx === 0) continue;

            $tanggalIdx = $colMap['tanggal_pembuatan'] ?? null;
            $awbIdx = $colMap['awb'] ?? null;
            $tanggalRaw = $tanggalIdx !== null ? trim((string) ($row[$tanggalIdx] ?? '')) : '';
            $awb = $awbIdx !== null ? trim((string) ($row[$awbIdx] ?? '')) : '';
            if (empty($tanggalRaw) || empty($awb)) continue;

            $tanggal = $this->parseDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            $sumberIdx = $colMap['sumber'] ?? null;
            $sumber = $sumberIdx !== null ? trim((string) ($row[$sumberIdx] ?? '')) : 'FLIK';
            $dashboard = $this->resolveDashboard($sumber);

            $kurirIdx = $colMap['kurir'] ?? null;
            $kurir = $kurirIdx !== null ? trim((string) ($row[$kurirIdx] ?? '')) : $dashboard;

            $codIdx = $colMap['cod'] ?? null;
            $codRaw = $codIdx !== null ? trim((string) ($row[$codIdx] ?? '0')) : '0';
            $codVal = $this->parseDecimal($codRaw);
            $jenis = $codVal > 0 ? 'COD' : 'TF';

            $namaProdukIdx = $colMap['nama_produk'] ?? null;
            $namaProdukRaw = $namaProdukIdx !== null ? trim((string) ($row[$namaProdukIdx] ?? '')) : '';
            $jumlah = 1;
            $cleanName = $namaProdukRaw;
            if (! empty($cleanName)) {
                $jumlah = $this->extractJumlah($cleanName);
                $cleanName = $this->cleanNamaProduk($cleanName);
            }
            $product = null;
            if (! empty($cleanName)) $product = $this->matchProduct($cleanName, $allProducts);
            if (! empty($namaProdukRaw) && ! $product) {
                if ($autoCreateProducts) {
                    $product = $this->createMissingProduct($namaProdukRaw);
                } else {
                    $errors[] = 'Baris '.($idx + 1).': Produk "'.$namaProdukRaw.'" tidak ditemukan.'; continue;
                }
            }

            $hargaDiskon = 0;
            $hargaIdx = $colMap['harga_setelah_diskon'] ?? null;
            if ($hargaIdx !== null) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$hargaIdx] ?? '0')));

            $detail = $this->buildDetail($row, $colMap, $codRaw, $tanggal, $kurir, $awb, $namaProdukRaw);

            $parsed[] = compact('tanggal', 'kurir', 'awb', 'jenis', 'namaProdukRaw', 'product', 'jumlah', 'hargaDiskon', 'detail', 'dashboard', 'cleanName');
            if ($product) $matchedIds[$product->id] = $product;
        }

        return $this->finalize($parsed, $errors, $matchedIds);
    }

    // ─── SPX parser ─────────────────────────────────────────────

    private function parseSpx(array $rows, Collection $allProducts, bool $autoCreateProducts = false): array
    {
        // Detect apakah row 0 adalah header (tanpa metadata row)
        $row0joined = implode(' ', array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]));
        $hasMeta = !str_contains($row0joined, 'tracking no') && !str_contains($row0joined, 'create time');
        $headerRow = $hasMeta ? 1 : 0;
        $dataStart = $hasMeta ? 2 : 1;

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[$headerRow]);
        $colMap = $this->mapHeadersSpx($headers);

        $parsed = [];
        $errors = [];
        $matchedIds = [];

        for ($idx = $dataStart; $idx < count($rows); $idx++) {
            $row = $rows[$idx];

            $awb = trim((string) ($row[0] ?? ''));
            $tglIdx = $colMap['tanggal_pembuatan'] ?? null;
            $tanggalRaw = $tglIdx !== null ? trim((string) ($row[$tglIdx] ?? '')) : '';
            if (empty($awb) || empty($tanggalRaw)) continue;

            $tanggal = $this->parseSpxDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            $kurir = 'SPX Express';
            $dashboard = 'SPX';

            // COD detection
            $codIdx = $colMap['cod'] ?? null;
            $codYn = $codIdx !== null ? strtolower(trim((string) ($row[$codIdx] ?? 'n'))) : 'n';
            $nomCodIdx = $colMap['nominal_cod'] ?? null;
            $codAmount = $nomCodIdx !== null ? $this->parseDecimal(trim((string) ($row[$nomCodIdx] ?? '0'))) : 0;
            $jenis = ($codYn === 'y' || $codAmount > 0) ? 'COD' : 'TF';

            $produkIdx = $colMap['nama_produk'] ?? null;
            $namaProdukRaw = $produkIdx !== null ? trim((string) ($row[$produkIdx] ?? '')) : '';
            $jumlah = 1;
            $jmlIdx = $colMap['jumlah'] ?? null;
            if ($jmlIdx !== null && is_numeric(trim((string) ($row[$jmlIdx] ?? '')))) {
                $jumlah = max(1, (int) trim((string) ($row[$jmlIdx] ?? '1')));
                $cleanName = $namaProdukRaw;
                $cleanName = trim(preg_replace('/\b\d+\s*pcs\b/i', '', $cleanName));
            } else {
                $cleanName = $namaProdukRaw;
                if (! empty($cleanName)) {
                    $jumlah = $this->extractJumlah($cleanName);
                    $cleanName = $this->cleanNamaProduk($cleanName);
                }
            }
            $cleanName = $this->cleanNamaProduk($cleanName);

            $product = null;
            if (! empty($cleanName)) $product = $this->matchProduct($cleanName, $allProducts);
            if (! empty($namaProdukRaw) && ! $product) {
                if ($autoCreateProducts) {
                    $product = $this->createMissingProduct($namaProdukRaw);
                } else {
                    $errors[] = 'Baris '.($idx + 1).': Produk "'.$namaProdukRaw.'" tidak ditemukan.'; continue;
                }
            }

            // Price
            $hargaDiskon = 0;
            $hargaIdx = $colMap['harga_setelah_diskon'] ?? null;
            if ($hargaIdx !== null) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$hargaIdx] ?? '0')));

            $detail = [
                'order_id' => trim((string) ($row[1] ?? '')),
                'awb' => $awb,
                'kurir' => $kurir,
                'service' => '',
                'tanggal_pembuatan' => $tanggal,
                'cod' => $jenis === 'COD' ? $codAmount : 0,
                'nama_shopper' => isset($colMap['nama_shopper']) ? trim((string) ($row[$colMap['nama_shopper']] ?? '')) : '',
                'no_telp' => isset($colMap['no_telp']) ? trim((string) ($row[$colMap['no_telp']] ?? '')) : '',
                'provinsi' => isset($colMap['provinsi']) ? trim((string) ($row[$colMap['provinsi']] ?? '')) : '',
                'kota' => isset($colMap['kota']) ? trim((string) ($row[$colMap['kota']] ?? '')) : '',
                'kecamatan' => isset($colMap['kecamatan']) ? trim((string) ($row[$colMap['kecamatan']] ?? '')) : '',
                'alamat_lengkap' => isset($colMap['alamat_lengkap']) ? trim((string) ($row[$colMap['alamat_lengkap']] ?? '')) : '',
                'nama_produk' => $namaProdukRaw,
                'status' => isset($colMap['status']) ? trim((string) ($row[$colMap['status']] ?? '')) : '',
                'catatan_kurir' => isset($colMap['catatan_kurir']) ? trim((string) ($row[$colMap['catatan_kurir']] ?? '')) : '',
                'harga_setelah_diskon' => $hargaDiskon,
            ];

            $parsed[] = compact('tanggal', 'kurir', 'awb', 'jenis', 'namaProdukRaw', 'product', 'jumlah', 'hargaDiskon', 'detail', 'dashboard', 'cleanName');
            if ($product) $matchedIds[$product->id] = $product;
        }

        return $this->finalize($parsed, $errors, $matchedIds);
    }

    // ─── SICEPAT parser ─────────────────────────────────────────

    private function parseSicepat(array $rows, Collection $allProducts, bool $autoCreateProducts = false): array
    {
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = $this->mapHeadersSicepat($headers);

        $parsed = [];
        $errors = [];
        $matchedIds = [];
        $kurir = 'SICEPAT';
        $dashboard = 'SICEPAT';

        foreach ($rows as $idx => $row) {
            if ($idx === 0) continue;

            $awbIdx = $colMap['awb'] ?? null;
            $tglIdx = $colMap['tanggal_pembuatan'] ?? null;
            $awb = $awbIdx !== null ? trim((string) ($row[$awbIdx] ?? '')) : '';
            $tanggalRaw = $tglIdx !== null ? trim((string) ($row[$tglIdx] ?? '')) : '';
            if (empty($awb) || empty($tanggalRaw)) continue;

            $tanggal = $this->parseDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            $bayarIdx = $colMap['tipe_bayar'] ?? null;
            $tipeBayar = $bayarIdx !== null ? strtolower(trim((string) ($row[$bayarIdx] ?? 'tf'))) : 'tf';
            $jenis = ($tipeBayar === 'cod') ? 'COD' : 'TF';

            $produkIdx = $colMap['nama_produk'] ?? null;
            $namaProdukRaw = $produkIdx !== null ? trim((string) ($row[$produkIdx] ?? '')) : '';
            $jumlah = 1;
            $jmlIdx = $colMap['jumlah'] ?? null;
            if ($jmlIdx !== null && is_numeric(trim((string) ($row[$jmlIdx] ?? '')))) {
                $jumlah = max(1, (int) trim((string) ($row[$jmlIdx] ?? '1')));
                $cleanName = $namaProdukRaw;
                $cleanName = trim(preg_replace('/\b\d+\s*pcs\b/i', '', $cleanName));
            } else {
                $cleanName = $namaProdukRaw;
                if (! empty($cleanName)) {
                    $jumlah = $this->extractJumlah($cleanName);
                    $cleanName = $this->cleanNamaProduk($cleanName);
                }
            }
            $cleanName = $this->cleanNamaProduk($cleanName);

            $product = null;
            if (! empty($cleanName)) $product = $this->matchProduct($cleanName, $allProducts);
            if (! empty($namaProdukRaw) && ! $product) {
                if ($autoCreateProducts) {
                    $product = $this->createMissingProduct($namaProdukRaw);
                } else {
                    $errors[] = 'Baris '.($idx + 1).': Produk "'.$namaProdukRaw.'" tidak ditemukan.'; continue;
                }
            }

            $hargaDiskon = 0;
            $hargaIdx = $colMap['harga_setelah_diskon'] ?? null;
            if ($hargaIdx !== null) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$hargaIdx] ?? '0')));

            $ongkir = 0;
            $ongkirIdx = $colMap['ongkir'] ?? null;
            if ($ongkirIdx !== null) $ongkir = $this->parseDecimal(trim((string) ($row[$ongkirIdx] ?? '0')));

            $detail = [
                'awb' => $awb,
                'kurir' => $kurir,
                'service' => isset($colMap['service']) ? trim((string) ($row[$colMap['service']] ?? '')) : '',
                'tanggal_pembuatan' => $tanggal,
                'cod' => $jenis === 'COD' ? 1 : 0,
                'nama_shopper' => isset($colMap['nama_shopper']) ? trim((string) ($row[$colMap['nama_shopper']] ?? '')) : '',
                'no_telp' => isset($colMap['no_telp']) ? trim((string) ($row[$colMap['no_telp']] ?? '')) : '',
                'provinsi' => isset($colMap['provinsi']) ? trim((string) ($row[$colMap['provinsi']] ?? '')) : '',
                'kota' => isset($colMap['kota']) ? trim((string) ($row[$colMap['kota']] ?? '')) : '',
                'kecamatan' => isset($colMap['kecamatan']) ? trim((string) ($row[$colMap['kecamatan']] ?? '')) : '',
                'alamat_lengkap' => isset($colMap['alamat_lengkap']) ? trim((string) ($row[$colMap['alamat_lengkap']] ?? '')) : '',
                'nama_produk' => $namaProdukRaw,
                'status' => isset($colMap['status']) ? trim((string) ($row[$colMap['status']] ?? '')) : '',
                'ongkir_sebelum_diskon' => $ongkir,
                'harga_setelah_diskon' => $hargaDiskon,
                'sumber' => 'SICEPAT',
            ];

            $parsed[] = compact('tanggal', 'kurir', 'awb', 'jenis', 'namaProdukRaw', 'product', 'jumlah', 'hargaDiskon', 'detail', 'dashboard', 'cleanName');
            if ($product) $matchedIds[$product->id] = $product;
        }

        return $this->finalize($parsed, $errors, $matchedIds);
    }

    // ─── Shared helpers ─────────────────────────────────────────

    private function finalize(array $raw, array $errors, array $matchedIds): array
    {
        $parsed = [];
        foreach ($raw as $r) {
            $parsed[] = [
                'tanggal' => $r['tanggal'],
                'kurir' => $r['kurir'],
                'awb' => $r['awb'],
                'jenis' => $r['jenis'],
                'nama_produk' => $r['namaProdukRaw'],
                'cleanName' => $r['cleanName'] ?? $r['namaProdukRaw'],
                'product_id' => $r['product']?->id,
                'jumlah' => $r['jumlah'],
                'harga_setelah_diskon' => $r['hargaDiskon'],
                'dashboard' => $r['dashboard'],
                'detail' => $r['detail'],
            ];
        }

        $grouped = $this->groupData($parsed);

        return [
            'data' => $parsed,
            'groups' => $grouped,
            'errors' => $errors,
            'total' => count($parsed),
            'matched_products' => $matchedIds,
        ];
    }

    public function groupData(array $parsed): array
    {
        $groups = [];
        foreach ($parsed as $row) {
            $key = $row['tanggal'].'|'.$row['dashboard'].'|'.$row['kurir'].'|'.$row['jenis'];
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'tanggal' => $row['tanggal'],
                    'kurir' => $row['kurir'],
                    'dashboard' => $row['dashboard'],
                    'jenis' => $row['jenis'],
                    'jumlah_resi' => 0,
                    'awbs' => [],
                    'products' => [],
                    'total_value' => 0,
                ];
            }
            $groups[$key]['jumlah_resi']++;
            $groups[$key]['awbs'][] = $row['awb'];
            $groups[$key]['total_value'] += $row['harga_setelah_diskon'];

            if ($row['product_id']) {
                $pid = $row['product_id'];
                $pkey = $pid . '|' . $row['cleanName'];
                if (! isset($groups[$key]['products'][$pkey])) {
                    $groups[$key]['products'][$pkey] = [
                        'product_id' => $pid,
                        'nama_produk' => $row['cleanName'],
                        'jumlah' => 0,
                    ];
                }
                $groups[$key]['products'][$pkey]['jumlah'] += $row['jumlah'];
            }
        }
        foreach ($groups as &$g) { $g['products'] = array_values($g['products']); }
        return array_values($groups);
    }

    private function resolveDashboard(string $sumber): string
    {
        $s = strtolower(trim($sumber));
        if (str_contains($s, 'flik')) return 'FLIK';
        if (str_contains($s, 'spx')) return 'SPX';
        if (str_contains($s, 'sicepat')) return 'SICEPAT';
        return strtoupper($sumber);
    }

    private function buildDetail(array $row, array $colMap, string $codRaw, string $tanggal, string $kurir, string $awb, string $namaProdukRaw): array
    {
        $detail = [];
        foreach ([
            'order_id', 'service', 'detail_penjemputan', 'nama_shopper', 'no_telp',
            'ongkir_sebelum_diskon', 'diskon', 'nominal_cod', 'status',
            'status_terakhir_dari_3pl', 'provinsi', 'catatan_kurir', 'pod',
            'scheduled_pickup', 'terakhir_update', 'nama_warehouse', 'sumber',
            'komisi_cod', 'komisi_jagokurir', 'actual_pickup', 'kecamatan', 'kota', 'alamat_lengkap',
        ] as $field) {
            if (isset($colMap[$field])) $detail[$field] = trim((string) ($row[$colMap[$field]] ?? ''));
        }
        $detail['cod'] = $codRaw;
        $detail['tanggal_pembuatan'] = $tanggal;
        $detail['kurir'] = $kurir;
        $detail['awb'] = $awb;
        $detail['nama_produk'] = $namaProdukRaw;
        return $detail;
    }

    // ─── SPX specific column mapping ────────────────────────────

    private function mapHeadersSpx(array $headers): array
    {
        $map = [
            'tanggal_pembuatan' => ['create time', 'create_time', 'tanggal', 'date'],
            'status' => ['tracking status', 'status', 'order status'],
            'nama_shopper' => ['recipient name', 'recipient name', 'penerima', 'nama_penerima', 'nama penerima'],
            'no_telp' => ['recipient phone number', 'no. hp', 'phone', 'telepon', 'no_telp'],
            'provinsi' => ['recipient province', 'province', 'provinsi'],
            'kota' => ['recipient city', 'city', 'kota', 'kabupaten'],
            'kecamatan' => ['recipient district', 'district', 'kecamatan'],
            'alamat_lengkap' => ['recipient detail address', 'recipient detail address', 'address', 'alamat', 'alamat_lengkap'],
            'nama_produk' => ['item in parcel', 'item in parcel', 'nama produk', 'nama_produk', 'item', 'produk'],
            'jumlah' => ['no. of item in parcel', 'no of item in parcel', 'jumlah item', 'qty', 'jumlah'],
            'cod' => ['cod collection(y/n)', 'cod collection', 'cod', 'cod_yn'],
            'nominal_cod' => ['cod amount', 'cod_amount', 'nominal cod'],
            'harga_setelah_diskon' => ['parcel value', 'parcel value', 'harga', 'value', 'total'],
            'catatan_kurir' => ['delivery failed reason', 'delivery failed', 'failed reason', 'alasan gagal', 'keterangan'],
        ];

        return $this->matchColMap($headers, $map);
    }

    // ─── SICEPAT specific column mapping ─────────────────────────

    private function mapHeadersSicepat(array $headers): array
    {
        $map = [
            'awb' => ['nomor resi', 'no resi', 'awb', 'resi', 'no_resi', 'nomor_resi'],
            'tanggal_pembuatan' => ['tanggal', 'date', 'tgl', 'tanggal_pembuatan', 'create time'],
            'status' => ['status', 'order status'],
            'nama_shopper' => ['nama penerima', 'nama_penerima', 'penerima', 'nama'],
            'no_telp' => ['no. hp penerima', 'no hp penerima', 'telepon', 'phone', 'no_telp', 'hp'],
            'provinsi' => ['provinsi', 'province', 'propinsi'],
            'kota' => ['kota', 'city', 'kabupaten'],
            'kecamatan' => ['kecamatan', 'district', 'sub district'],
            'alamat_lengkap' => ['alamat penerima', 'alamat_penerima', 'alamat', 'address', 'alamat_lengkap'],
            'nama_produk' => ['isi paket', 'isi_paket', 'nama produk', 'nama_produk', 'produk', 'item'],
            'jumlah' => ['jumlah isi paket', 'jumlah_isi_paket', 'jumlah', 'qty', 'quantity'],
            'tipe_bayar' => ['tipe pembayaran', 'tipe_pembayaran', 'pembayaran', 'payment type', 'payment'],
            'service' => ['layanan', 'service', 'jenis layanan'],
            'ongkir' => ['harga ongkir', 'harga_ongkir', 'ongkir', 'shipping cost', 'biaya kirim'],
            'harga_setelah_diskon' => ['harga paket', 'harga_paket', 'harga', 'price', 'total', 'total biaya', 'total_biaya'],
        ];

        return $this->matchColMap($headers, $map);
    }

    private function matchColMap(array $headers, array $map): array
    {
        $cleaned = [];
        foreach ($headers as $i => $header) {
            $h = trim((string) $header);
            $h = preg_replace('/^\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE/', '', $h);
            $h = preg_replace('/\s+/', ' ', $h);
            $cleaned[$i] = strtolower($h);
        }

        $result = [];
        foreach ($cleaned as $i => $lower) {
            foreach ($map as $key => $aliases) {
                if (in_array($lower, $aliases)) {
                    $result[$key] = $i;
                    break;
                }
            }
        }

        $fuzzy = [
            'awb' => ['tracking', 'no resi', 'resi', 'awb', 'nomor resi', 'tracking no'],
            'tanggal_pembuatan' => ['create time', 'tanggal', 'tgl', 'date', 'created'],
            'nama_produk' => ['item in parcel', 'item', 'isi paket', 'produk', 'nama produk', 'product'],
            'jumlah' => ['no. of item', 'jumlah isi', 'jumlah', 'qty', 'quantity'],
            'no_telp' => ['phone', 'hp', 'telepon', 'no_telp', 'no telp', 'recipient phone'],
            'nama_shopper' => ['recipient name', 'nama penerima', 'nama', 'shopper', 'penerima'],
            'harga_setelah_diskon' => ['parcel value', 'harga paket', 'total biaya', 'harga setelah', 'subtotal'],
            'catatan_kurir' => ['delivery failed reason', 'delivery failed', 'catatan', 'keterangan', 'failed reason'],
            'status' => ['tracking status', 'status'],
            'tipe_bayar' => ['tipe pembayaran', 'tipe', 'payment'],
            'cod' => ['cod collection', 'cod'],
            'nominal_cod' => ['cod amount', 'nominal cod'],
            'provinsi' => ['recipient province', 'province', 'provinsi'],
            'kota' => ['recipient city', 'city', 'kota', 'kabupaten'],
            'kecamatan' => ['recipient district', 'district', 'kecamatan'],
            'alamat_lengkap' => ['recipient detail address', 'alamat', 'address', 'alamat lengkap'],
            'kurir' => ['kurir', 'courier', 'ekspedisi'],
            'service' => ['service', 'layanan'],
        ];

        foreach ($fuzzy as $key => $keywords) {
            if (isset($result[$key])) continue;
            foreach ($cleaned as $i => $lower) {
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        $result[$key] = $i;
                        break 2;
                    }
                }
            }
        }

        return $result;
    }

    // ─── Legacy mapHeaders for FLIK ─────────────────────────────

    private function mapHeaders(array $headers): array
    {
        $map = [
            'order_id' => ['order id', 'order_id', 'id order', 'no order'],
            'awb' => ['awb', 'no resi', 'nomor resi', 'resi', 'no. resi', 'no_resi'],
            'kurir' => ['kurir', 'courier', 'ekspedisi', 'logistik'],
            'service' => ['service', 'layanan', 'jenis layanan'],
            'tanggal_pembuatan' => ['tanggal pembuatan', 'tgl pembuatan', 'tanggal', 'tgl', 'created_at', 'date', 'tanggal dibuat'],
            'detail_penjemputan' => ['detail penjemputan', 'detail_penjemputan', 'alamat penjemputan'],
            'cod' => ['cod', 'nominal cod', 'cod_amount', 'amount'],
            'nama_shopper' => ['nama shopper', 'nama_penerima', 'nama', 'shopper name', 'penerima'],
            'no_telp' => ['no telp', 'no_telp', 'telepon', 'phone', 'telp', 'no. telp', 'no telephone'],
            'ongkir_sebelum_diskon' => ['ongkir sebelum diskon', 'ongkir_sebelum_diskon', 'ongkir', 'shipping cost', 'biaya kirim'],
            'diskon' => ['diskon', 'discount', 'potongan'],
            'harga_setelah_diskon' => ['harga setelah diskon', 'harga_setelah_diskon', 'total', 'amount after discount', 'subtotal'],
            'nominal_cod' => ['nominal cod', 'nominal_cod', 'cod nominal'],
            'status' => ['status', 'order status'],
            'status_terakhir_dari_3pl' => ['status terakhir dari 3pl', 'status_terakhir_dari_3pl', 'status 3pl', 'last status'],
            'nama_produk' => ['nama produk', 'nama_produk', 'produk', 'product', 'product name', 'item', 'barang'],
            'provinsi' => ['provinsi', 'province', 'prov', 'propinsi'],
            'catatan_kurir' => ['catatan kurir', 'catatan_kurir', 'note', 'notes', 'keterangan'],
            'pod' => ['pod', 'proof of delivery', 'bukti kirim'],
            'scheduled_pickup' => ['scheduled pickup', 'scheduled_pickup', 'pickup schedule', 'jadwal penjemputan'],
            'terakhir_update' => ['terakhir update', 'terakhir_update', 'last update', 'last_updated'],
            'nama_warehouse' => ['nama warehouse', 'nama_warehouse', 'warehouse', 'gudang'],
            'sumber' => ['sumber', 'source', 'platform'],
            'komisi_cod' => ['komisi cod', 'komisi_cod', 'cod commission'],
            'komisi_jagokurir' => ['komisi jagokurir', 'komisi_jagokurir', 'jago kurir commission'],
            'actual_pickup' => ['actual pickup', 'actual_pickup', 'pickup actual'],
            'kecamatan' => ['kecamatan', 'district', 'sub district'],
            'kota' => ['kota', 'city', 'kabupaten', 'kab'],
            'alamat_lengkap' => ['alamat lengkap', 'alamat_lengkap', 'alamat', 'address', 'full address', 'alamat penerima', 'alamat lengkap penerima'],
        ];

        return $this->matchColMap($headers, $map);
    }

    // ─── Date/Number helpers ────────────────────────────────────

    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;

        if (is_numeric($dateStr) && $dateStr > 40000 && $dateStr < 200000) {
            try {
                $ts = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $dateStr);
                return date('Y-m-d', $ts);
            } catch (\Exception $e) {}
        }

        $clean = preg_replace('/[\s\-]+\d{2}[:.]\d{2}.*$/', '', $dateStr);
        $clean = trim($clean);

        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd.m.Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $clean);
            if ($dt && $dt->format($format) === $clean) return $dt->format('Y-m-d');
        }

        try { return (new \DateTime($clean))->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }

    private function parseSpxDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;
        // SPX format: "21-07-2026 22:55"
        $clean = preg_replace('/\s+\d{2}:\d{2}.*$/', '', $dateStr);
        $clean = trim($clean);
        $dt = \DateTime::createFromFormat('d-m-Y', $clean);
        if ($dt) return $dt->format('Y-m-d');
        // fallback to general parser
        return $this->parseDate($dateStr);
    }

    private function parseDecimal(string $val): float
    {
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/[^0-9.\-]/', '', $val);
        return (float) $val;
    }

    private function extractJumlah(string &$nama): int
    {
        $nama = trim($nama);
        if (preg_match('/\b(\d+)\s*pcs\b/i', $nama, $m)) {
            $jumlah = (int) $m[1];
            $nama = trim(preg_replace('/\b\d+\s*pcs\b/i', '', $nama));
            return max(1, $jumlah);
        }
        if (preg_match('/\b(\d+)\s*x\b/i', $nama, $m)) {
            $jumlah = (int) $m[1];
            $nama = trim(preg_replace('/\b\d+\s*x\b/i', '', $nama));
            return max(1, $jumlah);
        }
        return 1;
    }

    private function cleanNamaProduk(string $nama): string
    {
        $nama = preg_replace('/^\d+\s+/', '', $nama);
        return trim($nama);
    }

    private function createMissingProduct(string $namaProduk): Product
    {
        return Product::create([
            'nama_produk' => $namaProduk,
            'status' => 'aktif',
            'stok' => 0,
            'satuan' => 'pcs',
            'harga_jual' => 0,
            'harga_beli' => 0,
        ]);
    }

    private function resolveMissingProducts(array &$parsed, array &$groups, array &$matchedProducts): void
    {
        foreach ($parsed as &$row) {
            if (! $row['product_id']) {
                $name = $row['cleanName'] ?: $row['nama_produk'];
                if (! $name) continue;
                $product = $this->createMissingProduct($name);
                $row['product_id'] = $product->id;
                $matchedProducts[$product->id] = $product;
            }
        }

        $nameToId = [];
        foreach ($parsed as $row) {
            if ($row['product_id']) {
                $nameToId[$row['cleanName'] ?: $row['nama_produk']] = $row['product_id'];
            }
        }

        foreach ($groups as &$group) {
            foreach ($group['products'] as &$prod) {
                if (! $prod['product_id'] && isset($nameToId[$prod['nama_produk']])) {
                    $prod['product_id'] = $nameToId[$prod['nama_produk']];
                }
            }
            $group['products'] = array_values($group['products']);
        }
    }

    private function matchProduct(string $excelName, Collection $products): ?Product
    {
        $excelName = strtolower(trim($excelName));
        $exact = $products->first(fn ($p) => strtolower(trim($p->nama_produk)) === $excelName);
        if ($exact) return $exact;

        $excelWords = $this->tokenize($excelName);
        if (empty($excelWords)) return null;

        $minRequired = min(2, count($excelWords));
        $bestScore = 0;
        $best = null;
        foreach ($products as $p) {
            $dbWords = $this->tokenize(strtolower(trim($p->nama_produk)));
            $common = array_intersect($excelWords, $dbWords);
            if (empty($common)) continue;
            if (count($common) < $minRequired) continue;

            $score = count($common) + strlen($p->nama_produk) / 100;
            if ($score > $bestScore) { $bestScore = $score; $best = $p; }
        }
        return $best;
    }

    private function tokenize(string $name): array
    {
        $parts = preg_split('/[^a-z0-9+.\-\/\(\)]+/i', $name);
        $parts = array_filter($parts, fn ($w) => strlen($w) >= 2);
        return array_values(array_map('strtolower', $parts));
    }
}
