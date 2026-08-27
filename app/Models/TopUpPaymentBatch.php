<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopUpPaymentBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'batch_no',
        'payment_mode',
        'nominal',
        'va_number',
        'status',
        'bank_transfer_id',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TopUpProposal::class, 'proposal_id');
    }

    public function bankTransfer(): BelongsTo
    {
        return $this->belongsTo(BankTransfer::class, 'bank_transfer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TopUpProposalItem::class, 'payment_batch_id');
    }
}
