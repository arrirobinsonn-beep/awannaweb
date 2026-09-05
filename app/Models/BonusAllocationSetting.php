<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusAllocationSetting extends Model
{
    protected $fillable = ['advertiser_id', 'role', 'percentage'];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public const ROLES = ['advertiser', 'cs', 'keuangan', 'admin'];

    public const ROLE_LABELS = [
        'advertiser' => 'Advertiser',
        'cs' => 'CS',
        'keuangan' => 'Keuangan',
        'admin' => 'Admin',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    // ─── Scopes ──────────────────────────────────────────────

    /** Global settings (keuangan & admin) — advertiser_id null */
    public function scopeGlobal($query)
    {
        return $query->whereNull('advertiser_id');
    }

    /** Settings untuk advertiser tertentu */
    public function scopeForAdvertiser($query, int $advertiserId)
    {
        return $query->where('advertiser_id', $advertiserId);
    }
}
