<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Akun sumber uang perusahaan (rekening bank, cash, aggregator, ewallet, other).
 * `current_balance` dijaga otomatis oleh FinanceService dari bank_transfers
 * (approved) & account_transfers — jangan diedit manual lewat UI.
 */
class Account extends Model
{
    public const TYPES = ['cash', 'bank', 'aggregator', 'ewallet', 'other'];

    public const TYPE_LABELS = [
        'cash' => 'Cash',
        'bank' => 'Bank',
        'aggregator' => 'Agregator',
        'ewallet' => 'E-Wallet',
        'other' => 'Lainnya',
    ];

    public const STATUSES = ['active', 'inactive'];

    protected $table = 'accounts';

    protected $fillable = ['name', 'account_number', 'type', 'current_balance', 'status'];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    public function bankTransfers(): HasMany
    {
        return $this->hasMany(BankTransfer::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'from_account_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'to_account_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'active');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
