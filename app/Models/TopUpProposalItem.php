<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopUpProposalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'whitelist_id',
        'nominal',
        'va_number',
        'payment_status',
        'paid_at',
        'sisa_saldo_dilaporkan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'payment_status' => 'string',
        'paid_at' => 'datetime',
        'sisa_saldo_dilaporkan' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TopUpProposal::class, 'proposal_id');
    }

    public function whitelist(): BelongsTo
    {
        return $this->belongsTo(Whitelist::class);
    }

    // ─── Helper ────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }
}
