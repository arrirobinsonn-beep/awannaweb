<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'kode_supplier' => 'SUP-001',
                'nama_supplier' => 'PT Maju Bersama Sejahtera',
                'pic_nama' => 'Budi Santoso',
                'pic_telepon' => '0812-3456-7890',
                'email' => 'budi@majubersama.co.id',
                'alamat' => 'Jl. Industri Raya No. 12',
                'kota' => 'Jakarta Utara',
                'provinsi' => 'DKI Jakarta',
                'status' => 'aktif',
                'catatan' => 'Supplier utama skincare & beauty',
            ],
            [
                'kode_supplier' => 'SUP-002',
                'nama_supplier' => 'CV Herbal Nusantara',
                'pic_nama' => 'Sari Dewi',
                'pic_telepon' => '0856-7890-1234',
                'email' => 'sari@herbalnusantara.id',
                'alamat' => 'Jl. Raya Bogor KM 35',
                'kota' => 'Bogor',
                'provinsi' => 'Jawa Barat',
                'status' => 'aktif',
                'catatan' => 'Spesialis produk herbal & suplemen',
            ],
            [
                'kode_supplier' => 'SUP-003',
                'nama_supplier' => 'PT Digital Kreatif Indo',
                'pic_nama' => 'Andi Wijaya',
                'pic_telepon' => '0878-2345-6789',
                'email' => 'andi@digitalkreatif.com',
                'alamat' => 'Jl. Sudirman Blok A No. 5',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
                'status' => 'aktif',
                'catatan' => 'Supplier produk digital & aksesoris',
            ],
            [
                'kode_supplier' => 'SUP-004',
                'nama_supplier' => 'UD Sumber Makmur',
                'pic_nama' => 'Hendra Kusuma',
                'pic_telepon' => '0895-6789-0123',
                'email' => 'hendra@sumbermakmur.net',
                'alamat' => 'Jl. Pahlawan No. 88',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'status' => 'aktif',
                'catatan' => 'Produk fashion & lifestyle',
            ],
            [
                'kode_supplier' => 'SUP-005',
                'nama_supplier' => 'PT Teknologi Mandiri',
                'pic_nama' => 'Rini Pratiwi',
                'pic_telepon' => '0821-9876-5432',
                'email' => 'rini@tekmandir.co.id',
                'alamat' => 'Kawasan SCBD Lot 18',
                'kota' => 'Jakarta Selatan',
                'provinsi' => 'DKI Jakarta',
                'status' => 'nonaktif',
                'catatan' => 'Sementara nonaktif, sedang negosiasi ulang kontrak',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['kode_supplier' => $supplier['kode_supplier']], $supplier);
        }
    }
}
