<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris komposisi BOM: varian paket (product_variant_id) berisi
 * varian dasar (komponen_id) sebanyak qty.
 */
class ProductVariantItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'komponen_id',
        'qty',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    /** Varian paket pemilik baris komposisi ini */
    public function paket(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Varian dasar (unit) yang menjadi komponen */
    public function komponen(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'komponen_id');
    }
}
