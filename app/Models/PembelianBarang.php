<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianBarang extends Model
{
    protected $fillable = [
        'tanggal',
        'sumber_produk',
        'product_id',
        'qty',
        'harga_satuan',
        'total_belanja',
        'ongkir',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_belanja' => 'decimal:2',
        'ongkir' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
