<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // 1. Roles & permissions
            InventorySeeder::class,      // 2. Inventory (gudang)
            SupplierSeeder::class,       // 3. Supplier
            ProductSeeder::class,        // 4. Produk + varian (butuh inventory)
            UserSeeder::class,           // 5. User & assign role
            WhitelistSeeder::class,      // 6. Whitelist (butuh supplier & produk)
            SpendingHarianSeeder::class, // 7. Spending (butuh semua di atas)
            CourierRuleSeeder::class,    // 8. Rules pemilihan courier
            WarehouseRuleSeeder::class,  // 8b. Rules kode produk → gudang saat export
            TrackingStatusRuleSeeder::class, // 8c. Rules status aggregator → status sistem
            ExportTemplateMappingSeeder::class, // 9. Mapping export template (FLIK/SiCepat/SPX)
        ]);
    }
}
