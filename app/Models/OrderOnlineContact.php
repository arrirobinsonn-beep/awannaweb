<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOnlineContact extends Model
{
    protected $fillable = [
        'advertiser_id',
        'phone_normalized',
        'cs_name',
        'order_id',
        'buyer_name',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }
}
