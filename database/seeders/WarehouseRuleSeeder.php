<?php

namespace Database\Seeders;

use App\Models\WarehouseRule;
use Illuminate\Database\Seeder;

/**
 * Aturan gudang bawaan (idempotent — updateOrCreate by product_code):
 *   - SH  → GTM    (Shendara dikirim dari gudang GTM)
 *   - KSP → Aurora (Sporty dikirim dari gudang Aurora)
 *
 * Admin bisa ubah/hapus/tambah lewat halaman Aturan Gudang.
 */
class WarehouseRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['product_code' => 'SH', 'warehouse' => 'GTM'],
            ['product_code' => 'KSP', 'warehouse' => 'Aurora'],
        ] as $rule) {
            WarehouseRule::updateOrCreate(
                ['product_code' => $rule['product_code']],
                ['warehouse' => $rule['warehouse'], 'is_active' => true],
            );
        }
    }
}
