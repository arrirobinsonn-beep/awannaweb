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

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'active');
    }
}
