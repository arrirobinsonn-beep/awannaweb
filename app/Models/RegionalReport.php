<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalReport extends Model
{
    use HasFactory;

    /** Fase iklan asal baris (produk running/testing berdasar timeline produk). */
    public const PHASE_RUNNING = 'running';

    public const PHASE_TESTING = 'testing';

    public const PHASES = [self::PHASE_RUNNING, self::PHASE_TESTING];

    protected $fillable = [
        'tanggal',
        'user_id',
        'province',
        'ad_phase',
        'lead',
        'paid',
        'paid_ratio',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lead' => 'integer',
        'paid' => 'integer',
        'paid_ratio' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helper ────────────────────────────────────────────────

    /**
     * Hitung paid_ratio dari lead & paid.
     * Panggil sebelum create/update.
     */
    public static function computeRatio(array &$data): void
    {
        $lead = (int) ($data['lead'] ?? 0);
        $paid = (int) ($data['paid'] ?? 0);
        $data['paid_ratio'] = $lead > 0 ? round($paid / $lead * 100, 2) : 0;
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

    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    /**
     * KeyBy map existing per (tanggal|province|ad_phase) — dipakai savePreview.
     */
    public function scopeKeyedPhase($query)
    {
        return $query->get()->keyBy(
            fn ($r) => $r->tanggal->format('Y-m-d').'|'.$r->province.'|'.$r->ad_phase
        );
    }
}
