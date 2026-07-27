<?php

namespace App\Services;

use App\Models\PaketTracking;
use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UndelImportService
{
    public function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows) || count($rows) < 3) {
            throw new \Exception('File Excel kosong atau tidak memiliki data.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = $this->mapHeaders($headers);

        $errors = [];
        $rawRows = [];

        foreach ($rows as $idx => $row) {
            if ($idx === 0 || $idx === 1) continue;
            $awb = trim((string) ($row[$colMap['awb']] ?? ''));
            if (empty($awb)) continue;

            $colIdx = fn($key) => $colMap[$key] ?? -1;
            $rawRows[] = [
                'idx' => $idx,
                'awb' => $awb,
                'status' => trim((string) ($row[$colIdx('status')] ?? '')),
                'handle_by_raw' => trim((string) ($row[$colIdx('handle_by')] ?? '')),
                'catatan_kurir' => trim((string) ($row[$colIdx('catatan_kurir')] ?? '')),
                'no_telp' => trim((string) ($row[$colIdx('no_telp')] ?? '')),
                'nama_shopper' => trim((string) ($row[$colIdx('nama_shopper')] ?? '')),
                'nama_produk' => trim((string) ($row[$colIdx('nama_produk')] ?? '')),
                'create_time' => trim((string) ($row[$colIdx('create_time')] ?? '')),
            ];
        }

        $handleByMap = $this->buildHandleByMap($rawRows);

        $parsed = [];
        foreach ($rawRows as $r) {
            $parsed[] = [
                'awb' => $r['awb'],
                'status' => $r['status'],
                'handle_by' => $handleByMap[$r['handle_by_raw']] ?? ($r['handle_by_raw'] ?: null),
                'handle_by_raw' => $r['handle_by_raw'],
                'catatan_kurir' => $r['catatan_kurir'],
                'no_telp' => $r['no_telp'],
                'nama_shopper' => $r['nama_shopper'],
                'nama_produk' => $r['nama_produk'],
                'create_time' => $r['create_time'],
            ];
        }

        return [
            'data' => $parsed,
            'errors' => $errors,
            'total' => count($parsed),
        ];
    }

    private function buildHandleByMap(array $rawRows): array
    {
        $uniqueNames = [];
        foreach ($rawRows as $r) {
            $name = trim($r['handle_by_raw']);
            if (! empty($name)) $uniqueNames[$name] = true;
        }
        if (empty($uniqueNames)) return [];

        $keys = array_keys($uniqueNames);

        $csUsers = User::role('cs')
            ->where('is_active', true)
            ->get(['id', 'nama', 'panggilan']);

        $map = [];
        foreach ($keys as $raw) {
            $lowerRaw = strtolower($raw);
            $found = null;
            foreach ($csUsers as $cs) {
                $csName = strtolower(trim($cs->nama ?? ''));
                $csPanggilan = strtolower(trim($cs->panggilan ?? ''));
                if ($csName === $lowerRaw || $csPanggilan === $lowerRaw) {
                    $found = $cs->nama ?? $cs->panggilan;
                    break;
                }
            }
            $map[$raw] = $found ?: $raw;
        }

        return $map;
    }

    public function import(array $data): array
    {
        $updated = 0;
        $notFound = [];
        $errors = [];

        $awbList = collect($data['data'])->pluck('awb')->filter()->values()->toArray();

        $existing = PaketTracking::whereIn('awb', $awbList)->get()->keyBy('awb');

        foreach ($data['data'] as $row) {
            $awb = $row['awb'];
            if (empty($awb)) continue;

            $paket = $existing->get($awb);

            if (! $paket) {
                $notFound[] = $awb;
                continue;
            }

            $updateData = [];

            if (! empty($row['status'])) {
                $updateData['status'] = $row['status'];
            }
            if (! empty($row['handle_by'])) {
                $updateData['handle_by'] = $row['handle_by'];
            }
            if (! empty($row['catatan_kurir'])) {
                $updateData['catatan_kurir'] = $row['catatan_kurir'];
            }
            if (! empty($row['no_telp'])) {
                $updateData['no_telp'] = $row['no_telp'];
            }

            if (empty($updateData)) continue;

            try {
                $paket->update($updateData);
                $updated++;
            } catch (\Exception $e) {
                $errors[] = "AWB {$awb}: {$e->getMessage()}";
            }
        }

        return [
            'updated' => $updated,
            'not_found' => $notFound,
            'errors' => $errors,
        ];
    }

    private function mapHeaders(array $headers): array
    {
        $map = [
            'awb' => ['tracking no.', 'tracking no', 'awb', 'no resi', 'resi', 'no. resi'],
            'status' => ['tracking status', 'status', 'order status'],
            'handle_by' => ['handle by', 'handled by', 'cs', 'cs name', 'ditugaskan untuk'],
            'catatan_kurir' => ['delivery failed reason', 'failed reason', 'alasan gagal', 'catatan kurir', 'notes', 'keterangan'],
            'no_telp' => ['recipient phone number', 'no. hp', 'phone', 'telepon', 'no_telp', 'hp penerima', 'no telp'],
            'nama_shopper' => ['recipient name', 'nama penerima', 'penerima', 'nama shopper', 'nama'],
            'nama_produk' => ['item list', 'item in parcel', 'nama produk', 'produk', 'item', 'isi paket'],
            'create_time' => ['create time', 'tanggal pembuatan', 'tanggal', 'tgl', 'created_at'],
        ];

        $result = [];
        foreach ($headers as $i => $header) {
            $lower = preg_replace('/\s+/', ' ', strtolower(trim((string) $header)));
            foreach ($map as $key => $aliases) {
                if (in_array($lower, $aliases)) {
                    $result[$key] = $i;
                    break;
                }
            }
        }

        if (! isset($result['awb'])) {
            throw new \Exception('Kolom "Tracking No." / AWB tidak ditemukan di file Excel.');
        }

        return $result;
    }
}
