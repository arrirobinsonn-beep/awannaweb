<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $table = 'shipments';

    protected $fillable = [
        'source',
        'tracking_number',
        'product_id',
        'order_id',
        'courier',
        'service',
        'recipient_name',
        'phone',
        'full_address',
        'district',
        'city',
        'province',
        'postal_code',
        'product_name',
        'quantity',
        'shipping_fee',
        'parcel_value',
        'is_cod',
        'cod_amount',
        'status',
        'courier_note',
        'created_date',
        'pickup_date',
        'delivered_date',
        'source_file',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'shipping_fee' => 'decimal:2',
        'parcel_value' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'is_cod' => 'boolean',
        'created_date' => 'date',
        'pickup_date' => 'date',
        'delivered_date' => 'date',
    ];

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class, 'shipment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
