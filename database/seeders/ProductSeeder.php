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
                'kode_produk' => 'KMPU',
                'nama_produk' => 'Kacamata Multifokus Photochromic Unisex',
                'kategori' => 'Kacamata',
                'deskripsi' => 'Kacamata multifokus dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya, cocok untuk pria dan wanita.',
                'supplier_id' => $sup1,
                'harga_beli' => 20000,
                'harga_jual' => 119000,
                'stok' => 1000,
                'satuan' => 'Pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'KSP',
                'nama_produk' => 'Kacamata Sporty Phtochromic',
                'kategori' => 'Kacamata',
                'deskripsi' => 'Kacamata sporty dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya.',
                'supplier_id' => $sup1,
                'harga_beli' => 20000,
                'harga_jual' => 119000,
                'stok' => 1000,
                'satuan' => 'Pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'KBJ',
                'nama_produk' => 'Kacamata Baca dan Jalan',
                'kategori' => 'Kacamata',
                'deskripsi' => 'Kacamata baca dan jalan dengan lensa multifokus untuk kenyamanan pengguna.',
                'supplier_id' => $sup2,
                'harga_beli' => 25000,
                'harga_jual' => 119000,
                'stok' => 1000,
                'satuan' => 'Pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'KPL',
                'nama_produk' => 'Kacamata Polarized',
                'kategori' => 'Kacamata',
                'deskripsi' => 'Kacamata polarized dengan lensa yang mengurangi cahaya pantul dan memberikan kenyamanan pengguna.',
                'supplier_id' => $sup2,
                'harga_beli' => 25000,
                'harga_jual' => 119000,
                'stok' => 1000,
                'satuan' => 'Pcs',
                'status' => 'aktif',
            ],
            [
                'kode_produk' => 'SNDR',
                'nama_produk' => 'Shendara',
                'kategori' => 'Herbal',
                'deskripsi' => 'Lulur Kaki Herbal Shendara dengan bahan alami untuk perawatan kulit kaki yang lembut dan sehat.',
                'supplier_id' => $sup3,
                'harga_beli' => 3000,
                'harga_jual' => 110000,
                'stok' => 500,
                'satuan' => 'Sachet',
                'status' => 'aktif',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['kode_produk' => $product['kode_produk']], $product);
        }
    }
}
