<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
