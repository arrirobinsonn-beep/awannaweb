<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KirimanActualProduct extends Model
{
    protected $table = 'kiriman_actual_products';

    protected $fillable = [
        'kiriman_actual_id',
        'product_id',
        'jumlah',
    ];

    public function kirimanActual(): BelongsTo
    {
        return $this->belongsTo(KirimanActual::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
