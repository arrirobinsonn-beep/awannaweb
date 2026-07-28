<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gudang extends Model
{
    protected $fillable = ['nama'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
