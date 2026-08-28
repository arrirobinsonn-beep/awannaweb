<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Top Up', 'type' => 'out'],
            ['name' => 'Pembelian Barang', 'type' => 'out'],
            ['name' => 'Transfer Antar Akun', 'type' => 'out'],
            ['name' => 'Pendapatan', 'type' => 'in'],
            ['name' => 'Lainnya', 'type' => 'in'],
            ['name' => 'Lainnya', 'type' => 'out'],
        ];

        foreach ($categories as $cat) {
            TransactionCategory::updateOrCreate(
                ['name' => $cat['name'], 'type' => $cat['type']],
                []
            );
        }
    }
}
