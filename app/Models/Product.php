<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'inventory_id',
        'purchase_price',
        'selling_price',
        'unit',
        'status',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function spendingHarians(): HasMany
    {
        return $this->hasMany(SpendingHarian::class);
    }

    /** Varian produk (ukuran/power) — stok disimpan di sini */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // ─── Accessor ─────────────────────────────────────────────

    public function getMarginAttribute(): float
    {
        if ((float) $this->purchase_price <= 0) {
            return 0;
        }

        return round(((float) $this->selling_price - (float) $this->purchase_price) / (float) $this->purchase_price * 100, 0);
    }

    /**
     * Stok induk produk = gabungan stok semua varian (ukuran).
     * Berlaku saat relasi variants di-load; fallback ke sum jurnal stok varian.
     */
    public function getStokAttribute(): int
    {
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            return (int) $this->variants->sum('stock');
        }

        return (int) ProductVariant::where('product_id', $this->id)->sum('stock');
    }

    /**
     * Varian default: varian aktif pertama (urutan power terkecil).
     * Dipakai untuk shipment / order online yang tidak menyebut ukuran.
     */
    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants()
            ->where('status', 'active')
            ->orderBy('power')
            ->orderBy('id')
            ->first();
    }

    // ─── Scope ────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'active');
    }
}
