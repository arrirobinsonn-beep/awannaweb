<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // 1. Roles & permissions
            SupplierSeeder::class,       // 2. Supplier
            ProductSeeder::class,        // 3. Produk (butuh supplier)
            UserSeeder::class,           // 4. User & assign role
            WhitelistSeeder::class,      // 5. Whitelist (butuh supplier & produk)
            SpendingHarianSeeder::class, // 6. Spending (butuh semua di atas)
        ]);
    }
}
