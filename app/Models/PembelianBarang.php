<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianBarang extends Model
{
    protected $fillable = [
        'tanggal',
        'supplier',
        'supplier_id',
        'sumber_produk',
        'product_id',
        'qty',
        'harga_satuan',
        'total_belanja',
        'ongkir',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_belanja' => 'decimal:2',
        'ongkir' => 'decimal:2',
    ];

    public function supplierRel(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getSupplierNameAttribute(): string
    {
        return $this->supplierRel?->nama_supplier ?? $this->supplier ?? '-';
    }
}
