<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mapping header CSV dashboard aggregator → kolom database yang diisi saat
 * import tracking (tabel `tracking_header_mappings`).
 *
 * Dikelola admin per dashboard (FLIK / SiCepat / SPX) di halaman Aturan
 * Status — pola upload-mapping seperti export template. `db_column` harus
 * salah satu kunci dari COLUMNS (registry satu-satunya sumber kebenaran).
 */
class TrackingHeaderMapping extends Model
{
    protected $table = 'tracking_header_mappings';

    public const COLUMNS = [
        'tracking_number' => 'Nomor Resi / AWB (awb)',
        'phone' => 'No HP Pelanggan (phone_normalized)',
        'customer_name' => 'Nama Pelanggan (customer_name)',
        'address' => 'Alamat Lengkap (address)',
        'product_name' => 'Nama Produk / Isi Paket (product_name)',
        'quantity' => 'Jumlah / Qty (quantity)',
        'status' => 'Status mentah (aggregator_status)',
        'problem' => 'Kolom Masalah 3PL/OnHold (problem)',
        'delivered_date' => 'Tanggal Terkirim (delivered_at)',
    ];

    protected $fillable = [
        'source',
        'header',
        'db_column',
    ];
}
