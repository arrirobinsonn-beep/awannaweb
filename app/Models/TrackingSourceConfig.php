<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi kecil per dashboard tracking (tabel `tracking_source_configs`):
 * format nomor HP di file dashboard agar tetap bisa dicocokkan dengan
 * `phone_normalized` DB (berawalan 62).
 */
class TrackingSourceConfig extends Model
{
    protected $table = 'tracking_source_configs';

    /** Format No HP di file: auto (normalisasi otomatis) / 8 (SPX) / 0 / 62. */
    public const PHONE_FORMATS = ['auto', '8', '0', '62'];

    protected $fillable = [
        'source',
        'phone_format',
    ];
}
