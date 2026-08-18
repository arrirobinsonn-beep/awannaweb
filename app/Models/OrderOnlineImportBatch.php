<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderOnlineImportBatch extends Model
{
    protected $table = 'order_online_import_batches';

    protected $fillable = [
        'original_filename',
        'stored_path',
        'sender',
        'status',
        'total_rows',
        'processed_rows',
        'success_rows',
        'failed_rows',
        'error_message',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function shippingOrders(): HasMany
    {
        return $this->hasMany(ShippingOrder::class, 'order_online_import_batch_id');
    }
}
