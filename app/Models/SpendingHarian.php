<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpendingHarian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tanggal',
        'user_id',
        'whitelist_id',
        'product_id',
        'spending',
        'lead',
        'paid',
        'paid_ratio',
        'cpa_lead',
        'cpa_paid',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'spending' => 'decimal:2',
        'paid_ratio' => 'integer',
        'cpa_lead' => 'decimal:2',
        'cpa_paid' => 'decimal:2',
        'lead' => 'integer',
        'paid' => 'integer',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whitelist(): BelongsTo
    {
        return $this->belongsTo(Whitelist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Static helper: hitung & isi metric otomatis ───────────

    /**
     * Isi paid_ratio, cpa_lead, cpa_paid dari nilai spending/lead/paid.
     * Panggil sebelum create/update.
     */
    public static function computeMetrics(array &$data): void
    {
        $spending = (float) ($data['spending'] ?? 0);
        $lead = (int) ($data['lead'] ?? 0);
        $paid = (int) ($data['paid'] ?? 0);

        $data['paid_ratio'] = $lead > 0 ? round($paid / $lead * 100, 0) : 0;
        $data['cpa_lead'] = $lead > 0 ? round($spending / $lead, 2) : 0;
        $data['cpa_paid'] = $paid > 0 ? round($spending / $paid, 2) : 0;
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
}
