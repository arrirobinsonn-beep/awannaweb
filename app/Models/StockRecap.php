<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRecap extends Model
{
    protected $fillable = [
        'product_id',
        'bulan',
        'real_stok',
        'selisih',
    ];

    protected $casts = [
        'real_stok' => 'integer',
        'selisih' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
