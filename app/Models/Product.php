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
        'kode_produk',
        'nama_produk',
        'kategori',
        'deskripsi',
        'supplier_id',
        'gudang_id',
        'harga_beli',
        'harga_jual',
        'stok',
        'satuan',
        'gambar',
        'status',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'stok' => 'integer',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function whitelists(): HasMany
    {
        return $this->hasMany(Whitelist::class);
    }

    public function spendingHarians(): HasMany
    {
        return $this->hasMany(SpendingHarian::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // ─── Accessor ─────────────────────────────────────────────

    public function getGambarUrlAttribute(): string
    {
        return $this->gambar
            ? asset('storage/'.$this->gambar)
            : asset('images/no-image.png');
    }

    public function getMarginAttribute(): float
    {
        if ($this->harga_beli == 0) {
            return 0;
        }

        return round((($this->harga_jual - $this->harga_beli) / $this->harga_beli) * 100, 0);
    }

    // ─── Scope ────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
