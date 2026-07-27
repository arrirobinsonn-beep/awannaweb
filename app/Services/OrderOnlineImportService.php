<?php

namespace App\Services;

use App\Models\OrderOnlineContact;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrderOnlineImportService
{
    public function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows) || count($rows) < 2) {
            throw new \Exception('File Excel kosong atau tidak memiliki data.');
        }

        $parsed = [];
        $errors = [];

        foreach ($rows as $idx => $row) {
            if ($idx === 0) continue;

            $orderId = trim((string) ($row[0] ?? ''));
            $phoneRaw = trim((string) ($row[4] ?? ''));
            $buyerName = trim((string) ($row[2] ?? ''));
            $csName = trim((string) ($row[33] ?? ''));

            if (empty($phoneRaw)) continue;

            $phoneNormalized = $this->normalizePhone($phoneRaw);
            if (empty($phoneNormalized)) {
                $errors[] = 'Baris ' . ($idx + 1) . ': Nomor telepon tidak valid "' . $phoneRaw . '".';
                continue;
            }

            $parsed[] = [
                'order_id' => $orderId,
                'phone_normalized' => $phoneNormalized,
                'phone_raw' => $phoneRaw,
                'buyer_name' => $buyerName,
                'cs_name' => $csName,
            ];
        }

        return [
            'data' => $parsed,
            'errors' => $errors,
            'total' => count($parsed),
        ];
    }

    public function import(int $advertiserId, array $data): array
    {
        $inserted = 0;

        $chunks = array_chunk($data, 500);
        foreach ($chunks as $chunk) {
            $records = [];
            foreach ($chunk as $row) {
                $records[] = [
                    'advertiser_id' => $advertiserId,
                    'phone_normalized' => $row['phone_normalized'],
                    'cs_name' => $row['cs_name'],
                    'order_id' => $row['order_id'],
                    'buyer_name' => $row['buyer_name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            OrderOnlineContact::insert($records);
            $inserted += count($records);
        }

        return [
            'inserted' => $inserted,
        ];
    }

    public function resetByAdvertiser(int $advertiserId): void
    {
        OrderOnlineContact::where('advertiser_id', $advertiserId)->delete();
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 8) return '';
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '62')) {
            // already correct
        } else {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
