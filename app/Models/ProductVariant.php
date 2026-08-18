<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'code',
        'name',
        'jenis',
        'stock',
        'power',
        'status',
    ];

    protected $casts = [
        'stock' => 'integer',
        'power' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Stok per gudang (cache dari jurnal) — relasi ke product_variant_inventory. */
    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(ProductVariantInventory::class);
    }

    /**
     * Stok varian di satu gudang (dari cache product_variant_inventory).
     * Tanpa inventoryId → total semua gudang.
     */
    public function stockAt(?int $inventoryId = null): int
    {
        $query = ProductVariantInventory::where('product_variant_id', $this->id);
        if ($inventoryId !== null) {
            $query->where('inventory_id', $inventoryId);
        }

        return (int) $query->sum('stock');
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'active');
    }
}
