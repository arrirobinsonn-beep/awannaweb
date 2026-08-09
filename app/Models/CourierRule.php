<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierRule extends Model
{
    protected $table = 'courier_rules';

    protected $fillable = [
        'sort_order',
        'payment_method',
        'province',
        'courier',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
