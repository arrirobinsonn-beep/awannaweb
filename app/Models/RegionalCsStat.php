<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalCsStat extends Model
{
    protected $fillable = [
        'tanggal',
        'user_id',
        'cs_panggilan',
        'cs_user_id',
        'lead',
        'paid',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lead' => 'integer',
        'paid' => 'integer',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    /** Advertiser pemilik data */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** User CS yang menangani (nullable, jika cocok dengan sistem) */
    public function csUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_user_id');
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeByTanggal($query, string $dari, string $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    public function scopeMilikUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeMilikCs($query, ?int $csUserId)
    {
        if ($csUserId) {
            return $query->where('cs_user_id', $csUserId);
        }

        return $query;
    }
}
