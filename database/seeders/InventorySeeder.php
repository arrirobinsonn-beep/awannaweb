<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Gudang Pusat', 'GTM', 'Aurora'];

        foreach ($names as $name) {
            Inventory::firstOrCreate(['name' => $name]);
        }
    }
}
