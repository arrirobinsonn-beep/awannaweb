<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\PackagingRule;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** Produk kacamata yang punya ukuran power (10 power, +1.00..+3.00 step 0.25). */
    public const SIZED_PRODUCTS = ['KBJ', 'KMP', 'KSP', 'KDF', 'KP'];

    public function run(): void
    {
        $inventoryId = Inventory::orderBy('id')->first()?->id;

        // Produk inti dikirim dari gudang tertentu (selaras dgn WAREHOUSE_BY_PRODUCT
        // di export): SH→GTM, KSP→Aurora, sisanya → gudang pertama (Gudang Pusat).
        $inventoryByName = Inventory::pluck('id', 'name');
        $inventoryByCode = [
            'SH' => $inventoryByName['GTM'] ?? null,
            'KSP' => $inventoryByName['Aurora'] ?? null,
        ];

        $products = [
            [
                'code' => 'KMP',
                'name' => 'Kacamata Multifokus Photocromic',
                'category' => 'Kacamata',
                'goods_type' => 'core',
                'description' => 'Kacamata multifokus dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya.',
                'purchase_price' => 20000,
                'selling_price' => 119000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'KSP',
                'name' => 'Kacamata Sporty Photocromic',
                'category' => 'Kacamata',
                'goods_type' => 'core',
                'description' => 'Kacamata sporty dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya.',
                'purchase_price' => 20000,
                'selling_price' => 119000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'KBJ',
                'name' => 'Kacamata Baca & Jalan',
                'category' => 'Kacamata',
                'goods_type' => 'core',
                'description' => 'Kacamata baca dan jalan dengan lensa multifokus untuk kenyamanan pengguna.',
                'purchase_price' => 25000,
                'selling_price' => 119000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'KCHP',
                'name' => 'Kabel Casan Hp 3IN1',
                'category' => 'Aksesoris',
                'goods_type' => 'core',
                'description' => 'Kabel casan HP 3 in 1 yang kompatibel dengan berbagai merek smartphone.',
                'purchase_price' => 5000,
                'selling_price' => 25000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'SH',
                'name' => 'Shendara Herbal',
                'category' => 'Herbal',
                'goods_type' => 'core',
                'description' => 'Lulur Kaki Herbal Shendara dengan bahan alami untuk perawatan kulit kaki yang lembut dan sehat.',
                'purchase_price' => 3000,
                'selling_price' => 110000,
                'unit' => 'Sachet',
                'status' => 'active',
                'stok' => 500,
            ],
            [
                'code' => 'KNGH',
                'name' => 'Kreain Nature Gel Herbal',
                'category' => 'Herbal',
                'goods_type' => 'core',
                'description' => 'Gel herbal Kreain Nature dengan bahan alami untuk kesehatan dan perawatan tubuh.',
                'purchase_price' => 8000,
                'selling_price' => 45000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 500,
            ],
            [
                'code' => 'KP',
                'name' => 'Kacamata Polarized',
                'category' => 'Kacamata',
                'goods_type' => 'core',
                'description' => 'Kacamata polarized dengan lensa yang mengurangi cahaya terang dan memperbaiki kontras.',
                'purchase_price' => 8000,
                'selling_price' => 45000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 500,
            ],
            [
                'code' => 'KDF',
                'name' => 'Kacamata Double Fokus',
                'category' => 'Kacamata',
                'goods_type' => 'core',
                'description' => 'Kacamata double fokus pendamping Kacamata Baca & Jalan (KBJ), dikirim bersama untuk kombinasi yang lengkap.',
                'purchase_price' => 25000,
                'selling_price' => 119000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'BOX',
                'name' => 'Box Kacamata',
                'category' => 'Aksesoris',
                'goods_type' => 'additional',
                'description' => 'Box kemasan kacamata yang otomatis berkurang saat kacamata terkirim (rasio diatur di halaman Gudang).',
                'purchase_price' => 2000,
                'selling_price' => 5000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'LAP',
                'name' => 'Lap Pembersih',
                'category' => 'Aksesoris',
                'goods_type' => 'additional',
                'description' => 'Lap pembersih kacamata yang otomatis berkurang saat kacamata terkirim (rasio diatur di halaman Gudang).',
                'purchase_price' => 1500,
                'selling_price' => 5000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
            // ─── Barang Pasti (consumable) — stok dikelola manual oleh admin ───
            [
                'code' => 'KTH',
                'name' => 'Kertas Thermal',
                'category' => 'Konsumsi',
                'goods_type' => 'consumable',
                'description' => 'Kertas thermal untuk struk/resi. Barang habis pakai, stok ditambah/dikurangi manual oleh admin.',
                'purchase_price' => 5000,
                'selling_price' => 10000,
                'unit' => 'Roll',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'LAK',
                'name' => 'Lakban',
                'category' => 'Konsumsi',
                'goods_type' => 'consumable',
                'description' => 'Lakban perekat untuk packing. Barang habis pakai, stok ditambah/dikurangi manual oleh admin.',
                'purchase_price' => 3000,
                'selling_price' => 6000,
                'unit' => 'Roll',
                'status' => 'active',
                'stok' => 1000,
            ],
            [
                'code' => 'BUB',
                'name' => 'Bubble Wrap',
                'category' => 'Konsumsi',
                'goods_type' => 'consumable',
                'description' => 'Bubble wrap pelindung paket. Barang habis pakai, stok ditambah/dikurangi manual oleh admin.',
                'purchase_price' => 4000,
                'selling_price' => 8000,
                'unit' => 'Meter',
                'status' => 'active',
                'stok' => 1000,
            ],
        ];

        $stock = new StockService;

        foreach ($products as $product) {
            $invId = $inventoryByCode[$product['code']] ?? $inventoryId;
            $p = Product::firstOrCreate(
                ['code' => $product['code']],
                collect($product)->except('stok')->all()
            );

            // Pastikan kategori konsisten walau produk sudah ada (re-seed),
            // tanpa menimpa min_stock yang sudah di-set admin.
            if ((string) $p->goods_type !== (string) ($product['goods_type'] ?? 'core')) {
                $p->update(['goods_type' => $product['goods_type'] ?? 'core']);
            }

            // Keanggotaan gudang (many-to-many): Barang Pasti → semua gudang;
            // inti/additional → gudang induknya. Gudang UTAMA (is_primary) HANYA
            // untuk Barang Inti (core). Dipanggil SEBELUM seed varian agar opening
            // stock tahu gudang yang dituju.
            $this->syncInventoryMembership($p, $invId);
            $this->seedVariants($p, $product, $stock, $invId);
        }

        // Aturan kemasan dipanggil SETELAH produk dibuat agar berfungsi di DB fresh.
        $this->seedPackagingRules();
    }

    /**
     * Terdaftarkan produk di gudang (many-to-many) — idempotent.
     *  - Barang Pasti (consumable): ADA DI SEMUA gudang (stok manual per gudang).
     *  - Barang Inti/Additional: hanya di gudang induknya.
     * Gudang UTAMA (`is_primary`) HANYA untuk Barang Inti (core) — Barang Pasti
     * & Additional tidak pernah punya label gudang utama.
     */
    protected function syncInventoryMembership(Product $product, int $inventoryId): void
    {
        $isConsumable = $product->goods_type === Product::GOODS_CONSUMABLE;
        $ids = $isConsumable
            ? Inventory::orderBy('id')->pluck('id')->all()
            : [$inventoryId];
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $hasPrimary = $product->goods_type === Product::GOODS_CORE;

        // Re-seed memastikan is_primary kembali ke bawaan (paritas perilaku lama
        // yang memaksa inventory_id), tanpa menghapus keanggotaan tambahan yang
        // di-set admin lewat halaman Gudang.
        ProductInventory::where('product_id', $product->id)->update(['is_primary' => false]);

        foreach ($ids as $id) {
            ProductInventory::updateOrCreate(
                ['product_id' => $product->id, 'inventory_id' => $id],
                ['is_primary' => $hasPrimary && (int) $id === (int) $inventoryId]
            );
        }
    }

    /**
     * Aturan kemasan bawaan (idempotent):
     *  - additional (2:1): tiap 2 pcs kacamata (KMP/KSP/KBJ) → 1 BOX + 1 LAP keluar.
     *  - split (2:1): promo "Beli 1 Dapat 2" — KMP & KBJ dipecah dengan KDF
     *    (order qty 2 → 1 KMP + 1 KDF keluar; qty 4 → 2 + 2). KSP TIDAK dapat KDF.
     * Admin bisa ubah semua lewat halaman Gudang.
     */
    protected function seedPackagingRules(): void
    {
        $codes = ['KMP', 'KSP', 'KBJ'];
        $targets = ['BOX', 'LAP'];

        foreach ($codes as $sourceCode) {
            $source = Product::where('code', $sourceCode)->first();
            if (! $source) {
                continue;
            }

            foreach ($targets as $targetCode) {
                $target = Product::where('code', $targetCode)->first();
                if (! $target) {
                    continue;
                }

                PackagingRule::updateOrCreate(
                    ['source_product_id' => $source->id, 'target_product_id' => $target->id, 'inventory_id' => null],
                    ['qty_per' => 2, 'rule_type' => PackagingRule::TYPE_ADDITIONAL, 'is_active' => true]
                );
            }
        }

        // Promo split: KMP & KBJ → KDF (bonus), rasio 2:1
        foreach (['KMP', 'KBJ'] as $sourceCode) {
            $source = Product::where('code', $sourceCode)->first();
            $target = Product::where('code', 'KDF')->first();
            if (! $source || ! $target) {
                continue;
            }

            PackagingRule::updateOrCreate(
                ['source_product_id' => $source->id, 'target_product_id' => $target->id, 'inventory_id' => null],
                ['qty_per' => 2, 'rule_type' => PackagingRule::TYPE_SPLIT, 'is_active' => true]
            );
        }
    }

    /**
     * Buat varian produk + jurnal stok awal.
     * Produk berukuran → 10 varian power; selain itu → 1 varian default.
     * `designatedInventoryId` = gudang induk produk (dipakai opening stock
     * non-consumable — Barang Pasti dipecah per inventory di dalam seedVariant).
     */
    protected function seedVariants(Product $product, array $productData, StockService $stock, ?int $designatedInventoryId = null): void
    {
        $stokTotal = (int) $productData['stok'];

        if (in_array($productData['code'], self::SIZED_PRODUCTS)) {
            $powers = $this->powerList();
            $perVariant = (int) floor($stokTotal / count($powers));

            foreach ($powers as $power) {
                $this->seedVariant($product, [
                    'code' => $productData['code'].'+'.rtrim(rtrim(number_format($power, 2, '.', ''), '0'), '.'),
                    'name' => 'Plus +'.number_format($power, 2, ',', '.'),
                    'jenis' => 'ukuran',
                    'power' => $power,
                    'stock_awal' => $perVariant,
                ], $stock, $designatedInventoryId);
            }

            return;
        }

        $this->seedVariant($product, [
            'code' => $productData['code'],
            'name' => $productData['name'],
            'jenis' => null,
            'power' => 0,
            'stock_awal' => $stokTotal,
        ], $stock, $designatedInventoryId);
    }

    protected function seedVariant(Product $product, array $data, StockService $stock, ?int $designatedInventoryId = null): void
    {
        $variant = ProductVariant::firstOrCreate(
            ['product_id' => $product->id, 'code' => $data['code']],
            [
                'name' => $data['name'],
                'jenis' => $data['jenis'],
                'power' => $data['power'],
                'stock' => 0,
                'status' => 'active',
            ]
        );

        if ($data['stock_awal'] <= 0 || StockMovement::where('product_variant_id', $variant->id)
            ->where('reference', 'adjustment')
            ->where('type', 'in')
            ->exists()) {
            return;
        }

        $stokAwal = (int) $data['stock_awal'];
        $unitPrice = (float) $product->purchase_price;

        // Barang Pasti ada di SETIAP gudang → opening stock dibagi rata per inventory
        // (reference_id unik per gudang agar tidak menimpa jurnal satu sama lain).
        if ($product->goods_type === Product::GOODS_CONSUMABLE) {
            $inventories = Inventory::orderBy('id')->get();

            if ($inventories->isEmpty()) {
                $stock->recordIn($variant->id, now()->format('Y-m-d'), $stokAwal, $unitPrice, 'adjustment', $variant->id, 'Stok awal (seeder)', null, null);

                return;
            }

            $count = $inventories->count();
            $base = (int) floor($stokAwal / $count);
            $remaining = $stokAwal;

            foreach ($inventories as $i => $inv) {
                $qty = $i === $count - 1 ? $remaining : $base;
                $remaining -= $qty;
                if ($qty <= 0) {
                    continue;
                }
                $stock->recordIn(
                    $variant->id,
                    now()->format('Y-m-d'),
                    $qty,
                    $unitPrice,
                    'adjustment',
                    $variant->id * 100 + $inv->id,
                    'Stok awal (seeder) — '.$inv->name,
                    null,
                    $inv->id,
                );
            }

            return;
        }

        // Produk inti/additional: opening stock tercatat ke gudang induk produk
        // (designated) agar stok per gudang bisa dilihat di halaman Gudang.
        $stock->recordIn(
            $variant->id,
            now()->format('Y-m-d'),
            $stokAwal,
            $unitPrice,
            'adjustment',
            $variant->id,
            'Stok awal (seeder)',
            null,
            $designatedInventoryId ?? $product->primaryInventoryId(),
        );
    }

    /** Daftar power lensa: +1.00 s/d +3.00 step 0.25 (10 nilai). */
    protected function powerList(): array
    {
        $powers = [];
        for ($i = 1.00; $i <= 3.00; $i += 0.25) {
            $powers[] = round($i, 2);
        }

        return $powers;
    }
}
