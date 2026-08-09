<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AggregatorSyncBatch extends Model
{
    protected $table = 'aggregator_sync_batches';

    protected $fillable = [
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'matched_rows',
        'unmatched_rows',
        'phone_mismatch_rows',
        'status_updated_rows',
        'error_message',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'matched_rows' => 'integer',
        'unmatched_rows' => 'integer',
        'phone_mismatch_rows' => 'integer',
        'status_updated_rows' => 'integer',
    ];
}
