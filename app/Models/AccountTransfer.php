<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operan saldo antar akun (from → to). Tidak punya type in/out —
 * murni pemindahan saldo; current_balance diupdate oleh FinanceService.
 */
class AccountTransfer extends Model
{
    protected $table = 'account_transfers';

    protected $fillable = [
        'from_account_id', 'to_account_id', 'amount', 'description',
        'transfer_date', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'datetime',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
