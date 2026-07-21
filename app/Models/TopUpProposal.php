<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopUpProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'previous_topup_total',
        'today_lead',
        'today_paid',
        'today_spending',
        'total_nominal',
        'approver_id',
        'decline_note',
        'approved_at',
        'declined_at',
        'va_paid_at',
        'va_paid_by',
    ];

    protected $casts = [
        'previous_topup_total' => 'decimal:2',
        'today_spending' => 'decimal:2',
        'total_nominal' => 'decimal:2',
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'completed_at' => 'datetime',
        'va_paid_at' => 'datetime',
        'today_lead' => 'integer',
        'today_paid' => 'integer',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Approver (super admin / owner yang approve atau decline) */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** Super admin yang mengkonfirmasi VA sudah dibayar */
    public function vaPayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'va_paid_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TopUpProposalItem::class, 'proposal_id');
    }

    // ─── Helper ────────────────────────────────────────────────

    /** Apakah proposal sudah bisa dibayar (approved) */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function isMenungguPembayaran(): bool
    {
        return $this->status === 'menunggu_pembayaran';
    }

    /** Apakah Super Admin sudah mengkonfirmasi VA dibayar? */
    public function isVaPaid(): bool
    {
        return $this->va_paid_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** Hitung total item yang sudah dibayar */
    public function paidItemsCount(): int
    {
        return $this->items()->where('payment_status', 'paid')->count();
    }

    /** Hitung total item yang belum dibayar */
    public function pendingItemsCount(): int
    {
        return $this->items()->where('payment_status', 'pending')->count();
    }

    /** Semua item sudah dibayar? */
    public function isFullyPaid(): bool
    {
        return $this->items()->count() > 0
            && $this->items()->where('payment_status', 'pending')->doesntExist();
    }

    /** Semua item sudah dikonfirmasi sisa saldo? */
    public function isAllSisaSaldoReported(): bool
    {
        return $this->items()->count() > 0
            && $this->items()->whereNull('sisa_saldo_dilaporkan')->doesntExist();
    }
}
