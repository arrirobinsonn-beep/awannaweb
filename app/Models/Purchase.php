<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'rejected'];
    public const STATUS_LABELS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    protected $fillable = [
        'date',
        'supplier_id',
        'product_variant_id',
        'inventory_id',
        'quantity',
        'unit_price',
        'shipping_cost',
        'note',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
        'rejection_note',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // ─── Relasi ────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Helper ────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        if ($status && in_array($status, self::STATUSES, true)) {
            return $query->where('status', $status);
        }

        return $query;
    }
}
