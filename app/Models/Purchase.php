<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED  = 'received';

    public const STATUSES = [self::STATUS_IN_TRANSIT, self::STATUS_RECEIVED];
    public const STATUS_LABELS = [
        self::STATUS_IN_TRANSIT => 'Belum Masuk',
        self::STATUS_RECEIVED  => 'Diterima',
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
        'received_qty',
        'received_note',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'received_qty' => 'integer',
        'received_at' => 'datetime',
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ─── Helper ────────────────────────────────────────────────

    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function totalCost(): float
    {
        return (float) $this->quantity * (float) $this->unit_price + (float) $this->shipping_cost;
    }

    // ─── Scope ─────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        if ($status && in_array($status, self::STATUSES, true)) {
            return $query->where('status', $status);
        }

        return $query;
    }
}
