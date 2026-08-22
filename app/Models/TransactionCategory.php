<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori transaksi bank_transfers — mis. "Bank Transfer" (type in),
 * "Biaya" (type out).
 */
class TransactionCategory extends Model
{
    public const TYPES = ['in', 'out'];

    public const TYPE_LABELS = [
        'in' => 'Masuk',
        'out' => 'Keluar',
    ];

    protected $table = 'transaction_categories';

    protected $fillable = ['name', 'type'];

    public function bankTransfers(): HasMany
    {
        return $this->hasMany(BankTransfer::class, 'category_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
