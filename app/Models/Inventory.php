<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inventory extends Model
{
    protected $fillable = ['name'];

    /** Produk yang terdaftar di gudang ini (many-to-many via product_inventory). */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_inventory')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
