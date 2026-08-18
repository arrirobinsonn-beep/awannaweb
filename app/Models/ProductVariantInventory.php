<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stok per varian per gudang — cache turunan dari jurnal `stock_movements`
 * (di-sinkronkan StockService). Jurnal tetap sumber kebenaran; kolom `stock`
 * di sini mempercepat tampilan & query stok per gudang.
 */
class ProductVariantInventory extends Model
{
    protected $table = 'product_variant_inventory';

    protected $fillable = [
        'product_variant_id',
        'inventory_id',
        'stock',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
