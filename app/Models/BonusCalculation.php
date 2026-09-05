<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusCalculation extends Model
{
    protected $fillable = [
        'period', 'user_id', 'spending', 'lead', 'paid',
        'paid_ratio', 'adjustment', 'cpa_paid', 'margin', 'pengali', 'potensi_bonus',
        'status', 'approved_by', 'approved_at', 'disbursed_at', 'notes',
    ];

    protected $casts = [
        'spending' => 'decimal:2',
        'paid_ratio' => 'decimal:4',
        'adjustment' => 'decimal:4',
        'cpa_paid' => 'decimal:2',
        'margin' => 'decimal:2',
        'pengali' => 'decimal:2',
        'potensi_bonus' => 'decimal:2',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public const STATUSES = ['draft', 'approved', 'disbursed'];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'approved' => 'Disetujui',
        'disbursed' => 'Ditransfer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDisbursed(): bool
    {
        return $this->status === 'disbursed';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
