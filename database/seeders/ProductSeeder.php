<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sup1 = Supplier::where('kode_supplier', 'SUP-001')->first()?->id;
        $sup2 = Supplier::where('kode_supplier', 'SUP-002')->first()?->id;
        $sup3 = Supplier::where('kode_supplier', 'SUP-003')->first()?->id;
        $sup4 = Supplier::where('kode_supplier', 'SUP-004')->first()?->id;

        $products = [
            [
                'kode_produk' => 'PRD-001',
                'nama_produk' => 'Serum Vitamin C Brightening',
                'kategori' => 'Skincare',
                'deskripsi' => 'Serum dengan kandungan Vitamin C 20% untuk mencerahkan dan meratakan warna kulit.',
                'supplier_id' => $sup1,
                'harga_beli' => 45000,
                'harga_jual' => 129000,
                'stok' => 250,
                'satuan' => 'botol',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-002',
                'nama_produk' => 'Moisturizer SPF 30',
                'kategori' => 'Skincare',
                'deskripsi' => 'Pelembab harian dengan perlindungan SPF 30, cocok untuk semua jenis kulit.',
                'supplier_id' => $sup1,
                'harga_beli' => 35000,
                'harga_jual' => 89000,
                'stok' => 180,
                'satuan' => 'tube',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-003',
                'nama_produk' => 'Suplemen Kolagen Plus',
                'kategori' => 'Suplemen',
                'deskripsi' => 'Suplemen kolagen premium dengan tambahan Biotin dan Vitamin E untuk kulit elastis.',
                'supplier_id' => $sup2,
                'harga_beli' => 80000,
                'harga_jual' => 220000,
                'stok' => 120,
                'satuan' => 'box',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-004',
                'nama_produk' => 'Herbal Slimming Tea',
                'kategori' => 'Herbal',
                'deskripsi' => 'Teh herbal pelangsing alami dari bahan pilihan tanpa efek samping.',
                'supplier_id' => $sup2,
                'harga_beli' => 25000,
                'harga_jual' => 75000,
                'stok' => 300,
                'satuan' => 'sachet',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-005',
                'nama_produk' => 'Phone Stand Magnetic',
                'kategori' => 'Aksesoris',
                'deskripsi' => 'Stand HP magnetik 360 derajat untuk meja atau dashboard mobil.',
                'supplier_id' => $sup3,
                'harga_beli' => 15000,
                'harga_jual' => 49000,
                'stok' => 500,
                'satuan' => 'pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-006',
                'nama_produk' => 'Wireless Earbuds Pro',
                'kategori' => 'Elektronik',
                'deskripsi' => 'Earbuds TWS dengan noise cancelling aktif dan baterai 30 jam.',
                'supplier_id' => $sup3,
                'harga_beli' => 120000,
                'harga_jual' => 299000,
                'stok' => 75,
                'satuan' => 'unit',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-007',
                'nama_produk' => 'Kaos Oversize Premium',
                'kategori' => 'Fashion',
                'deskripsi' => 'Kaos oversize bahan cotton combed 30s, tersedia berbagai warna.',
                'supplier_id' => $sup4,
                'harga_beli' => 45000,
                'harga_jual' => 120000,
                'stok' => 200,
                'satuan' => 'pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'PRD-008',
                'nama_produk' => 'Tote Bag Canvas',
                'kategori' => 'Fashion',
                'deskripsi' => 'Tote bag canvas premium dengan jahitan kuat dan desain minimalis.',
                'supplier_id' => $sup4,
                'harga_beli' => 30000,
                'harga_jual' => 85000,
                'stok' => 150,
                'satuan' => 'pcs',
                'status' => 'aktif',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['kode_produk' => $product['kode_produk']], $product);
        }
    }
}
