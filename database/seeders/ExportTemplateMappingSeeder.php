<?php

namespace Database\Seeders;

use App\Models\ExportTemplate;
use App\Models\ExportTemplateMapping;
use Illuminate\Database\Seeder;

/**
 * Mapping export bawaan — meniru persis array header & isi kolom yang dulu
 * hardcoded di OrderTemplateExportService (FLIK 16, SiCepat 27, SPX 22 kolom).
 * Setelah di-seed, admin bebas mengedit lewat menu Aturan Export, bahkan
 * membuat template baru (tabel `export_templates`).
 */
class ExportTemplateMappingSeeder extends Seeder
{
    public function run(): void
    {
        ExportTemplateMapping::query()->truncate();
        ExportTemplate::query()->truncate();

        ExportTemplate::insert([
            ['key' => 'flik', 'name' => 'FLIK', 'couriers' => json_encode(['flix-tf', 'flix-idx', 'flix-sicepat', 'flix-spx']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sicepat', 'name' => 'SiCepat', 'couriers' => json_encode(['sicepat']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'spx', 'name' => 'SPX', 'couriers' => json_encode(['spx']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedTemplate('flik', [
            ['Kode Warehouse', 'computed', 'warehouse'],
            ['Nama Pelanggan', 'column', 'customer_name'],
            ['No HP Pelanggan (mulai dengan "62")', 'column', 'phone_normalized'],
            ['Alamat: Lengkap', 'column', 'address'],
            ['Alamat: Provinsi', 'column', 'province'],
            ['Alamat: Kota', 'column', 'city'],
            ['Alamat: Kecamatan', 'column', 'subdistrict'],
            ['Alamat: Kelurahan', 'empty', null],
            ['Alamat: Kode Pos', 'column', 'postal_code'],
            ['Alamat: Catatan Kurir', 'computed', 'default_courier_note'],
            ['Total Nilai Barang / Total Nilai COD', 'column', 'amount'],
            ['Panjang Barang (cm)', 'computed', 'pack_length'],
            ['Lebar Barang (cm)', 'computed', 'pack_width'],
            ['Tinggi Barang (cm)', 'computed', 'pack_height'],
            ['Berat (kg)', 'computed', 'weight_1'],
            ['Nama Produk', 'computed', 'product_name_display'],
        ]);

        $this->seedTemplate('sicepat', [
            ['Penerima', 'column', 'customer_name'],
            ['No.HP Penerima', 'column', 'phone_normalized'],
            ['Jumlah Paket', 'column', 'quantity'],
            ['No.Referensi (Maksimal 50 Karakter)', 'computed', 'order_id_50'],
            ['Alamat Penerima', 'column', 'address'],
            ['Kecamatan', 'column', 'subdistrict'],
            ['Kota/Kabupaten', 'column', 'city'],
            ['Kode Pos', 'column', 'postal_code'],
            ['Layanan', 'empty', null],
            ['Jenis Paket', 'static', 'Barang'],
            ['Isi Paket', 'computed', 'product_name_display'],
            ['Berat Paket (Kg)', 'computed', 'weight_1'],
            ['Panjang Paket', 'computed', 'pack_length'],
            ['Lebar Paket', 'computed', 'pack_width'],
            ['Tinggi Paket', 'computed', 'pack_height'],
            ['Harga Paket', 'column', 'amount'],
            ['Packing Kayu', 'empty', null],
            ['Proteksi Paket?', 'empty', null],
            ['Total COD', 'computed', 'cod_amount'],
            ['COD Ongkir?', 'empty', null],
            ['Catatan Pengiriman', 'computed', 'default_courier_note'],
            ['Tipe DO Balik', 'empty', null],
            ['Tipe Alamat DO Balik', 'empty', null],
            ['Alamat DO Balik', 'empty', null],
            ['Kecamatan DO Balik', 'empty', null],
            ['Kota/Kabupaten DO Balik', 'empty', null],
            ['Kode Pos DO Balik', 'empty', null],
        ]);

        $this->seedTemplate('spx', [
            ['*Nomor Pesanan', 'column', 'order_id'],
            ['*Nama Penerima // *Recipient Name', 'column', 'customer_name'],
            ['*Nomor Telepon Penerima // *Recipient Phone', 'computed', 'phone_spx'],
            ['*Alamat Lengkap // *Detail Address', 'column', 'address'],
            ['*Provinsi // *Province', 'computed', 'province_upper'],
            ['*Kota // *City', 'computed', 'city_upper'],
            ['*Kecamatan // *District', 'computed', 'district_upper'],
            ['*Kode Pos // *Postal Code', 'column', 'postal_code'],
            ['*Berat Paket (KG) // *Parcel Weight (KG)', 'computed', 'weight_1'],
            ['*Harga Barang // *Parcel Value', 'column', 'amount'],
            ['*COD? (Paket COD/Bukan Paket COD) // *COD? (COD Parcel//Non-COD Parcel)', 'computed', 'cod_flag'],
            ['*Nominal COD yang harus ditagihkan ke Penerima // * COD Amount', 'computed', 'cod_amount'],
            ['*Asuransi (Y/N) / *Insurance (Y/N)', 'static', 'N'],
            ['Panjang Paket (CM) // Parcel Length (CM)', 'computed', 'pack_length'],
            ['Lebar Paket (CM) // Parcel Width (CM)', 'computed', 'pack_width'],
            ['Tinggi Paket (CM) // Parcel Height (CM)', 'computed', 'pack_height'],
            ['*Nama Barang // *Item Name', 'computed', 'product_name_display'],
            ['Jumlah Barang // Item Quantity', 'column', 'quantity'],
            ['Harga Barang // Item Price', 'column', 'amount'],
            ['Nomer Referensi Pembeli // Customer Reference Number', 'column', 'order_id'],
            ['*Metode Pembayaran // *Payment Method', 'computed', 'payment_method_upper'],
            ['Instruksi Pengiriman // Delivery Instruction', 'computed', 'default_courier_note'],
        ]);
    }

    /**
     * @param  array<int, array{0:string, 1:string, 2:string|null}>  $rows  [header, source_type, source_value]
     */
    private function seedTemplate(string $template, array $rows): void
    {
        foreach ($rows as $i => [$header, $type, $value]) {
            ExportTemplateMapping::create([
                'template' => $template,
                'column_index' => $i,
                'header' => $header,
                'source_type' => $type,
                'source_value' => $value,
                'is_active' => true,
            ]);
        }
    }
}
