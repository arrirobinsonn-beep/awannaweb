<?php

namespace Database\Seeders;

use App\Models\CourierRule;
use Illuminate\Database\Seeder;

class CourierRuleSeeder extends Seeder
{
    public function run(): void
    {
        CourierRule::query()->truncate();

        $rules = [];

        // 1. Produk SH → SELALU flix-tf (khusus produk, menang atas aturan
        //    provinsi/metode bayar — evaluasi 2 fase di CourierRuleService).
        $rules[] = ['sort_order' => 1, 'payment_method' => null, 'province' => null, 'product_code' => 'SH', 'courier' => 'flix-tf'];

        // 2. bank_transfer → flix-tf (semua provinsi)
        $rules[] = ['sort_order' => 2, 'payment_method' => 'bank_transfer', 'province' => null, 'courier' => 'flix-tf'];

        // 3. COD → flix-idx (Sumatera tertentu)
        $sort = 3;
        foreach ([
            'BENGKULU', 'JAMBI', 'LAMPUNG', 'RIAU',
            'SUMATRA BARAT', 'SUMATRA SELATAN', 'SUMATRA UTARA',
        ] as $province) {
            $rules[] = ['sort_order' => $sort++, 'payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx'];
        }

        // 3. COD → sicepat (seluruh Jawa + Bali)
        foreach ([
            'BANTEN', 'DKI JAKARTA', 'JAWA BARAT', 'JAWA TENGAH', 'JAWA TIMUR', 'DI YOGYAKARTA', 'BALI',
        ] as $province) {
            $rules[] = ['sort_order' => $sort++, 'payment_method' => 'cod', 'province' => $province, 'courier' => 'sicepat'];
        }

        // 4. COD → flix-spx (pulau lainnya di luar flix-idx & sicepat)
        foreach ([
            'NANGGROE ACEH DARUSSALAM (NAD)', 'BANGKA BELITUNG', 'KEPULAUAN RIAU', 'GORONTALO',
            'KALIMANTAN BARAT', 'KALIMANTAN SELATAN', 'KALIMANTAN TENGAH', 'KALIMANTAN TIMUR', 'KALIMANTAN UTARA',
            'SULAWESI BARAT', 'SULAWESI SELATAN', 'SULAWESI TENGAH', 'SULAWESI TENGGARA', 'SULAWESI UTARA',
            'MALUKU', 'MALUKU UTARA', 'NUSA TENGGARA BARAT (NTB)', 'NUSA TENGGARA TIMUR (NTT)',
            'PAPUA', 'PAPUA BARAT',
        ] as $province) {
            $rules[] = ['sort_order' => $sort++, 'payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-spx'];
        }

        foreach ($rules as $rule) {
            CourierRule::create($rule);
        }
    }
}
