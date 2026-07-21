<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Whitelist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'kode',
        'platform',
        'user_id',
        'tanggal',
        'status',
        'total_topup',
        'total_spending',
        'nominal_terakhir_topup',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_topup' => 'decimal:2',
        'total_spending' => 'decimal:2',
        'nominal_terakhir_topup' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    /** Pemilik whitelist (user/advertiser) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spendingHarians(): HasMany
    {
        return $this->hasMany(SpendingHarian::class);
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // ─── Accessor ──────────────────────────────────────────────

    public function getSisaSaldoAttribute(): float
    {
        return (float) $this->total_topup - (float) $this->total_spending;
    }

    // ─── Rekalkulasi total_spending ──────────────────────────

    /**
     * Hitung ulang total_spending dari seluruh SpendingHarian milik whitelist ini.
     * Panggil setiap kali ada create/update/delete data spending.
     */
    public function recalculateTotalSpending(): void
    {
        $total = $this->spendingHarians()->sum('spending');
        $this->update(['total_spending' => $total]);
    }
}
