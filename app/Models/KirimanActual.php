<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KirimanActual extends Model
{
    protected $fillable = [
        'tanggal',
        'jenis',
        'dashboard',
        'jumlah_resi',
        'value_resi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_resi' => 'integer',
        'value_resi' => 'decimal:2',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(KirimanActualProduct::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
