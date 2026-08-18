<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagingRule extends Model
{
    /** Aturan "additional": tiap `qty_per` source → 1 target keluar (target pakai varian default). */
    public const TYPE_ADDITIONAL = 'additional';

    /** Aturan "split": source keluar `ceil(qty/qty_per)`, target keluar `floor(qty/qty_per)` (varian power sama). */
    public const TYPE_SPLIT = 'split';

    public const TYPES = [self::TYPE_ADDITIONAL, self::TYPE_SPLIT];

    /**
     * Aturan kemasan: setiap `qty_per` unit produk inti (source) terkirim,
     * barang target ikut keluar sesuai jenis aturan (`additional` / `split`).
     */
    protected $fillable = [
        'source_product_id',
        'target_product_id',
        'inventory_id',
        'qty_per',
        'rule_type',
        'is_active',
    ];

    protected $casts = [
        'qty_per' => 'integer',
        'rule_type' => 'string',
        'is_active' => 'boolean',
    ];

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    /** Gudang tempat rule berlaku; null = semua gudang. */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
