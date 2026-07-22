<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaketTracking extends Model
{
    protected $fillable = [
        'kiriman_actual_id',
        'order_id', 'awb', 'kurir', 'service', 'tanggal_pembuatan',
        'detail_penjemputan', 'cod', 'nama_shopper', 'no_telp',
        'ongkir_sebelum_diskon', 'diskon', 'harga_setelah_diskon',
        'nominal_cod', 'status', 'status_terakhir_dari_3pl',
        'nama_produk', 'provinsi', 'catatan_kurir', 'pod',
        'scheduled_pickup', 'terakhir_update', 'nama_warehouse',
        'sumber', 'komisi_cod', 'komisi_jagokurir', 'actual_pickup',
        'kecamatan', 'kota', 'alamat_lengkap',
    ];

    protected $casts = [
        'tanggal_pembuatan' => 'date',
        'cod' => 'decimal:2',
        'ongkir_sebelum_diskon' => 'decimal:2',
        'diskon' => 'decimal:2',
        'harga_setelah_diskon' => 'decimal:2',
        'nominal_cod' => 'decimal:2',
        'komisi_cod' => 'decimal:2',
        'komisi_jagokurir' => 'decimal:2',
    ];

    public function kirimanActual(): BelongsTo
    {
        return $this->belongsTo(KirimanActual::class);
    }
}
