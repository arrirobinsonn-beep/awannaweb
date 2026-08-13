<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot many-to-many produk ↔ gudang.
 * `is_primary` = gudang UTAMA produk (dipakai export/fulfillment &
 * pencatatan stok otomatis saat pengiriman).
 */
class ProductInventory extends Model
{
    protected $table = 'product_inventory';

    protected $fillable = [
        'product_id',
        'inventory_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
