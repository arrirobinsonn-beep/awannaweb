<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'goods_type',
        'min_stock',
        'description',
        'purchase_price',
        'selling_price',
        'unit',
        'status',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'min_stock' => 'integer',
    ];

    /** Kategori barang gudang: consumable / core / additional. */
    public const GOODS_CONSUMABLE = 'consumable';

    public const GOODS_CORE = 'core';

    public const GOODS_ADDITIONAL = 'additional';

    public const GOODS_TYPES = [
        self::GOODS_CONSUMABLE,
        self::GOODS_CORE,
        self::GOODS_ADDITIONAL,
    ];

    /** Label bahasa Indonesia untuk tiap kategori barang. */
    public const GOODS_TYPE_LABELS = [
        self::GOODS_CONSUMABLE => 'Barang Pasti',
        self::GOODS_CORE => 'Barang Inti',
        self::GOODS_ADDITIONAL => 'Barang Additional',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    /**
     * Gudang tempat produk terdaftar (many-to-many). `is_primary` menandai
     * gudang utama produk.
     */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'product_inventory')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** Gudang UTAMA produk (0/1 baris) — dipakai export & reserve stok. */
    public function primaryInventory(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'product_inventory')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    /** Id gudang utama (tanpa memuat relasi penuh bila belum di-load). */
    public function primaryInventoryId(): ?int
    {
        // Gudang utama HANYA bermakna untuk Barang Inti (core). Barang Pasti
        // (consumable) ada di semua gudang & Barang Additional mengikuti barang
        // inti — keduanya tidak boleh melaporkan gudang utama apa pun.
        // goods_type null = instance baru dari create() (DB default 'core' belum
        // di-refresh ke model) → diperlakukan sebagai core.
        if ($this->goods_type !== null && $this->goods_type !== self::GOODS_CORE) {
            return null;
        }

        if ($this->relationLoaded('primaryInventory')) {
            $id = $this->primaryInventory->first()?->id;

            return $id !== null ? (int) $id : null;
        }

        $id = ProductInventory::where('product_id', $this->id)
            ->where('is_primary', true)
            ->value('inventory_id');

        return $id !== null ? (int) $id : null;
    }

    /** Total stok produk di satu gudang (jumlah semua varian, dari cache). */
    public function stockAt(int $inventoryId): int
    {
        return (int) ProductVariantInventory::where('inventory_id', $inventoryId)
            ->whereIn('product_variant_id', $this->variants()->pluck('id'))
            ->sum('stock');
    }

    public function spendingHarians(): HasMany
    {
        return $this->hasMany(SpendingHarian::class);
    }

    /** Aturan kemasan di mana produk ini menjadi barang inti (source). */
    public function packagingRulesAsSource(): HasMany
    {
        return $this->hasMany(PackagingRule::class, 'source_product_id');
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
