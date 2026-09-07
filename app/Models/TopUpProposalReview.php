<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopUpProposalReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'reviewer_id',
        'decision',
        'suggested_total_nominal',
        'note',
    ];

    protected $casts = [
        'suggested_total_nominal' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TopUpProposal::class, 'proposal_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
