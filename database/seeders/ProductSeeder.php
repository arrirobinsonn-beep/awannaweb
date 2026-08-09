<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** Produk kacamata yang punya ukuran power (10 power, +1.00..+3.00 step 0.25). */
    public const SIZED_PRODUCTS = ['KBJ', 'KMP', 'KSP', 'KDF'];

    public function run(): void
    {
        $inventoryId = Inventory::orderBy('id')->first()?->id;

        $products = [
            [
                'code' => 'KMP',
                'name' => 'Kacamata Multifokus Photocromic',
                'category' => 'Kacamata',
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
                'description' => 'Gel herbal Kreain Nature dengan bahan alami untuk kesehatan dan perawatan tubuh.',
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
                'description' => 'Box kemasan kacamata yang otomatis berkurang saat kacamata terkirim (1 box per 2 pcs).',
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
                'description' => 'Lap pembersih kacamata yang otomatis berkurang saat kacamata terkirim (1 lap per 2 pcs).',
                'purchase_price' => 1500,
                'selling_price' => 5000,
                'unit' => 'Pcs',
                'status' => 'active',
                'stok' => 1000,
            ],
        ];

        $stock = new StockService;

        foreach ($products as $product) {
            $p = Product::firstOrCreate(
                ['code' => $product['code']],
                collect($product)->except('stok')->merge(['inventory_id' => $inventoryId])->all()
            );

            $this->seedVariants($p, $product, $stock);
        }
    }

    /**
     * Buat varian produk + jurnal stok awal.
     * Produk berukuran → 10 varian power; selain itu → 1 varian default.
     */
    protected function seedVariants(Product $product, array $productData, StockService $stock): void
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
                ], $stock);
            }

            return;
        }

        $this->seedVariant($product, [
            'code' => $productData['code'],
            'name' => $productData['name'],
            'jenis' => null,
            'power' => 0,
            'stock_awal' => $stokTotal,
        ], $stock);
    }

    protected function seedVariant(Product $product, array $data, StockService $stock): void
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

        if ($data['stock_awal'] > 0 && ! StockMovement::where('product_variant_id', $variant->id)
            ->where('reference', 'adjustment')
            ->where('type', 'in')
            ->exists()) {
            $stock->recordIn(
                $variant->id,
                now()->format('Y-m-d'),
                (int) $data['stock_awal'],
                (float) $product->purchase_price,
                'adjustment',
                $variant->id,
                'Stok awal (seeder)',
            );
        }
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
