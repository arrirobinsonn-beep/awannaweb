<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsAssignment extends Model
{
    protected $fillable = [
        'cs_user_id',
        'advertiser_id',
        'bulan',
        'created_by',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    /** User CS yang ditempatkan */
    public function csUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_user_id');
    }

    /** Advertiser yang menjadi 'CS utama untuk' pada bulan tersebut */
    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    /** Admin yang mengatur penempatan */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scope ─────────────────────────────────────────────────

    /** Filter berdasarkan periode berlaku (format 'Y-m') */
    public function scopeBulan($query, string $bulan)
    {
        return $query->where('bulan', $bulan);
    }
}
