<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aturan dinamis kode produk → gudang (nama pengirim pada export template).
 * Satu kode produk = satu rule; nonaktifkan agar produk jatuh ke gudang
 * utama produk (pivot is_primary) lalu sender.
 */
class WarehouseRule extends Model
{
    protected $table = 'warehouse_rules';

    protected $fillable = [
        'product_code',
        'warehouse',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
