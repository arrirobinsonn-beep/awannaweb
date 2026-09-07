<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device mobile yang terikat dengan satu account.
 * Token plain-text HANYA dikembalikan saat pembuatan/regenerate;
 * database hanya menyimpan token_hash.
 */
class MobileDevice extends Model
{
    public const STATUSES = ['active', 'revoked'];

    public const STATUS_LABELS = [
        'active'  => 'Aktif',
        'revoked' => 'Dicabut',
    ];

    protected $table = 'mobile_devices';

    protected $fillable = ['account_id', 'name', 'token_hash', 'status', 'last_used_at'];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
