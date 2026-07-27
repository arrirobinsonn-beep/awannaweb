<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
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

        if ($autoCreateProducts) {
            $this->resolveMissingProducts($result['data'], $result['groups'], $result['matched_products']);
        }

        return $result;
    }

    // ─── Format detection ───────────────────────────────────────

    private function detectFormat(array $rows): string
    {
        $first = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $joined = implode(' ', $first);

        // SPX: first cell contains "Report Download Time"
        if (str_contains($joined, 'report download time')) {
            return 'spx';
        }

        // SICEPAT: has "nomor resi" AND "isi paket" AND "jumlah isi paket"
        if (str_contains($joined, 'nomor resi') && str_contains($joined, 'isi paket')) {
            return 'sicepat';
        }

        // FLIK: has "order id" + "sumber"
        if (str_contains($joined, 'order id') || str_contains($joined, 'sumber') || str_contains($joined, 'flik')) {
            return 'flik';
        }

        // Fallback: try FLIK parsing anyway
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

            $tanggalRaw = trim((string) ($row[$colMap['tanggal_pembuatan']] ?? ''));
            $awb = trim((string) ($row[$colMap['awb']] ?? ''));
            if (empty($tanggalRaw) || empty($awb)) continue;

            $tanggal = $this->parseDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            $sumber = isset($colMap['sumber'])
                ? trim((string) ($row[$colMap['sumber']] ?? ''))
                : 'FLIK';
            $dashboard = $this->resolveDashboard($sumber);

            $kurir = trim((string) ($row[$colMap['kurir']] ?? $dashboard));

            $codRaw = trim((string) ($row[$colMap['cod'] ?? -1] ?? '0'));
            $codVal = $this->parseDecimal($codRaw);
            $jenis = $codVal > 0 ? 'COD' : 'TF';

            $namaProdukRaw = isset($colMap['nama_produk']) ? trim((string) ($row[$colMap['nama_produk']] ?? '')) : '';
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
            if (isset($colMap['harga_setelah_diskon'])) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$colMap['harga_setelah_diskon']] ?? '0')));

            $detail = $this->buildDetail($row, $colMap, $codRaw, $tanggal, $kurir, $awb, $namaProdukRaw);

            $parsed[] = compact('tanggal', 'kurir', 'awb', 'jenis', 'namaProdukRaw', 'product', 'jumlah', 'hargaDiskon', 'detail', 'dashboard', 'cleanName');
            if ($product) $matchedIds[$product->id] = $product;
        }

        return $this->finalize($parsed, $errors, $matchedIds);
    }

    // ─── SPX parser ─────────────────────────────────────────────

    private function parseSpx(array $rows, Collection $allProducts, bool $autoCreateProducts = false): array
    {
        // Row 0 = metadata, Row 1 = actual headers, Row 2+ = data
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[1]);
        $colMap = $this->mapHeadersSpx($headers);

        $parsed = [];
        $errors = [];
        $matchedIds = [];

        for ($idx = 2; $idx < count($rows); $idx++) {
            $row = $rows[$idx];

            $awb = trim((string) ($row[0] ?? ''));
            $tanggalRaw = trim((string) ($row[$colMap['tanggal_pembuatan']] ?? ''));
            if (empty($awb) || empty($tanggalRaw)) continue;

            $tanggal = $this->parseSpxDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            $kurir = 'SPX Express';
            $dashboard = 'SPX';

            // COD detection
            $codYn = strtolower(trim((string) ($row[$colMap['cod']] ?? 'n')));
            $codAmount = $this->parseDecimal(trim((string) ($row[$colMap['nominal_cod']] ?? '0')));
            $jenis = ($codYn === 'y' || $codAmount > 0) ? 'COD' : 'TF';

            $namaProdukRaw = trim((string) ($row[$colMap['nama_produk']] ?? ''));
            $jumlah = 1;
            if (isset($colMap['jumlah']) && is_numeric(trim((string) ($row[$colMap['jumlah']] ?? '')))) {
                $jumlah = max(1, (int) trim((string) ($row[$colMap['jumlah']] ?? '1')));
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
            if (isset($colMap['harga_setelah_diskon'])) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$colMap['harga_setelah_diskon']] ?? '0')));

            $detail = [
                'order_id' => trim((string) ($row[1] ?? '')), // Tracking link
                'awb' => $awb,
                'kurir' => $kurir,
                'service' => '',
                'tanggal_pembuatan' => $tanggal,
                'cod' => $jenis === 'COD' ? $codAmount : 0,
                'nama_shopper' => trim((string) ($row[$colMap['nama_shopper']] ?? '')),
                'no_telp' => trim((string) ($row[$colMap['no_telp']] ?? '')),
                'provinsi' => trim((string) ($row[$colMap['provinsi']] ?? '')),
                'kota' => trim((string) ($row[$colMap['kota']] ?? '')),
                'kecamatan' => trim((string) ($row[$colMap['kecamatan']] ?? '')),
                'alamat_lengkap' => trim((string) ($row[$colMap['alamat_lengkap']] ?? '')),
                'nama_produk' => $namaProdukRaw,
                'status' => trim((string) ($row[$colMap['status']] ?? '')),
                'catatan_kurir' => trim((string) ($row[$colMap['catatan_kurir']] ?? '')),
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

            $awb = trim((string) ($row[$colMap['awb']] ?? ''));
            $tanggalRaw = trim((string) ($row[$colMap['tanggal_pembuatan']] ?? ''));
            if (empty($awb) || empty($tanggalRaw)) continue;

            $tanggal = $this->parseDate($tanggalRaw);
            if (! $tanggal) { $errors[] = 'Baris '.($idx + 1).': Tanggal tidak valid "'.$tanggalRaw.'".'; continue; }

            // COD detection from Tipe Pembayaran
            $tipeBayar = strtolower(trim((string) ($row[$colMap['tipe_bayar']] ?? 'tf')));
            $jenis = ($tipeBayar === 'cod') ? 'COD' : 'TF';

            $namaProdukRaw = trim((string) ($row[$colMap['nama_produk']] ?? ''));
            $jumlah = 1;
            if (isset($colMap['jumlah']) && is_numeric(trim((string) ($row[$colMap['jumlah']] ?? '')))) {
                $jumlah = max(1, (int) trim((string) ($row[$colMap['jumlah']] ?? '1')));
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
            if (isset($colMap['harga_setelah_diskon'])) $hargaDiskon = $this->parseDecimal(trim((string) ($row[$colMap['harga_setelah_diskon']] ?? '0')));

            $ongkir = 0;
            if (isset($colMap['ongkir'])) $ongkir = $this->parseDecimal(trim((string) ($row[$colMap['ongkir']] ?? '0')));

            $detail = [
                'awb' => $awb,
                'kurir' => $kurir,
                'service' => trim((string) ($row[$colMap['service']] ?? '')),
                'tanggal_pembuatan' => $tanggal,
                'cod' => $jenis === 'COD' ? 1 : 0,
                'nama_shopper' => trim((string) ($row[$colMap['nama_shopper']] ?? '')),
                'no_telp' => trim((string) ($row[$colMap['no_telp']] ?? '')),
                'provinsi' => trim((string) ($row[$colMap['provinsi']] ?? '')),
                'kota' => trim((string) ($row[$colMap['kota']] ?? '')),
                'kecamatan' => trim((string) ($row[$colMap['kecamatan']] ?? '')),
                'alamat_lengkap' => trim((string) ($row[$colMap['alamat_lengkap']] ?? '')),
                'nama_produk' => $namaProdukRaw,
                'status' => trim((string) ($row[$colMap['status']] ?? '')),
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

    private function groupData(array $parsed): array
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
        $result = [];
        foreach ($headers as $i => $header) {
            $lower = strtolower(trim((string) $header));
            foreach ($map as $key => $aliases) {
                if (in_array($lower, $aliases)) {
                    $result[$key] = $i;
                    break;
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

        $result = [];
        foreach ($headers as $i => $header) {
            $lower = strtolower(trim((string) $header));
            foreach ($map as $key => $aliases) {
                if (in_array($lower, $aliases)) {
                    $result[$key] = $i;
                    break;
                }
            }
        }
        return $result;
    }

    // ─── Date/Number helpers ────────────────────────────────────

    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;

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
