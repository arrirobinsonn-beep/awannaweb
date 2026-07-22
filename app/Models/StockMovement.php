<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'gudang',
        'tanggal',
        'masuk_belanja',
        'masuk_rts',
        'masuk_repair',
        'barang_rusak',
        'barang_keluar',
        'catatan',
        'kiriman_actual_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'masuk_belanja' => 'integer',
        'masuk_rts' => 'integer',
        'masuk_repair' => 'integer',
        'barang_rusak' => 'integer',
        'barang_keluar' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function kirimanActual(): BelongsTo
    {
        return $this->belongsTo(KirimanActual::class);
    }
}
