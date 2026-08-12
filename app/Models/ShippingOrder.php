<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingOrder extends Model
{
    protected $table = 'shipping_orders';

    protected $fillable = [
        'order_online_import_batch_id',
        'order_id',
        'awb',
        'customer_name',
        'phone',
        'phone_normalized',
        'address',
        'province',
        'city',
        'subdistrict',
        'postal_code',
        'payment_method',
        'status',
        'handled_by',
        'handled_by_user_id',
        'courier',
        'courier_note',
        'product_name',
        'meta_account',
        'product_code',
        'product_id',
        'product_variant_id',
        'stock_note',
        'quantity',
        'weight',
        'amount',
        'is_cod',
        'shipping_cost',
        'aggregator_status',
        'last_synced_at',
        'delivered_at',
        'raw_payload',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:3',
        'amount' => 'decimal:2',
        'is_cod' => 'boolean',
        'shipping_cost' => 'decimal:2',
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public const EXPORTABLE_STATUSES = ['real', 'tembakan'];

    public const STATUSES = ['real', 'tembakan', 'belum_diproses', 'cancel', 'duplikat'];

    /** Nilai aggregator_status (tracking dari dashboard FLIK/SiCepat/SPX). */
    public const TRACKING_STATUSES = ['waiting_pickup', 'in_transit', 'delivered', 'returning', 'returned', 'problem'];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(OrderOnlineImportBatch::class, 'order_online_import_batch_id');
    }

    public function handledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isExportable(): bool
    {
        return in_array($this->status, self::EXPORTABLE_STATUSES, true);
    }
}
