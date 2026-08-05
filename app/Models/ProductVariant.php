<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'kode',
        'nama',
        'jenis',
        'stok',
        'pcs_per_pack',
        'harga_jual',
        'status',
    ];

    protected $casts = [
        'stok' => 'integer',
        'pcs_per_pack' => 'integer',
        'harga_jual' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    // ─── Accessor ──────────────────────────────────────────────

    /**
     * Margin varian = (harga_jual − modal) / modal × 100,
     * dengan modal = HPP/PCS produk × isi paket (pcs_per_pack).
     * isi paket = berapa pcs yang didapat pembeli dalam 1 paket (mis. beli 1 dapat 2).
     */
    public function getMarginAttribute(): float
    {
        $cost = (float) ($this->product->harga_beli ?? 0) * max(1, (int) $this->pcs_per_pack);

        if ($cost <= 0) {
            return 0;
        }

        return round((((float) $this->harga_jual - $cost) / $cost) * 100, 0);
    }
}
