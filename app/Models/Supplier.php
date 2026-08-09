<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode_supplier',
        'nama_supplier',
        'pic_nama',
        'pic_telepon',
        'email',
        'alamat',
        'kota',
        'provinsi',
        'status',
        'catatan',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // ─── Scope ────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
