<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\BankTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Satu-satunya titik update `accounts.current_balance`.
 *
 * - applyBankTransfer   : in = +amount, out = -amount (HANYA saat approved)
 * - reverseBankTransfer : kebalikan (dipakai saat hapus transaksi approved)
 * - applyAccountTransfer: from -=, to += (validasi saldo cukup)
 * - reverseAccountTransfer: kebalikan (hapus transfer)
 *
 * Semua operasi dalam transaksi + lockForUpdate agar tidak double-apply
 * saat dua request berbarengan.
 */
class FinanceService
{
    public function applyBankTransfer(BankTransfer $bt): void
    {
        if (! $bt->isApproved()) {
            return;
        }

        DB::transaction(function () use ($bt) {
            $account = Account::whereKey($bt->account_id)->lockForUpdate()->firstOrFail();
            $delta = $bt->type === 'in' ? (float) $bt->amount : - (float) $bt->amount;
            $account->update(['current_balance' => (float) $account->current_balance + $delta]);
        });
    }

    public function reverseBankTransfer(BankTransfer $bt): void
    {
        if (! $bt->isApproved()) {
            return;
        }

        DB::transaction(function () use ($bt) {
            $account = Account::whereKey($bt->account_id)->lockForUpdate()->firstOrFail();
            $delta = $bt->type === 'in' ? - (float) $bt->amount : (float) $bt->amount;
            $account->update(['current_balance' => (float) $account->current_balance + $delta]);
        });
    }

    public function applyAccountTransfer(AccountTransfer $at): void
    {
        DB::transaction(function () use ($at) {
            $from = Account::whereKey($at->from_account_id)->lockForUpdate()->firstOrFail();
            $to = Account::whereKey($at->to_account_id)->lockForUpdate()->firstOrFail();

            if ((float) $from->current_balance < (float) $at->amount) {
                throw new RuntimeException(
                    "Saldo akun '{$from->name}' tidak mencukupi (Rp "
                    . number_format((float) $from->current_balance, 0, ',', '.') . ').'
                );
            }

            $from->update(['current_balance' => (float) $from->current_balance - (float) $at->amount]);
            $to->update(['current_balance' => (float) $to->current_balance + (float) $at->amount]);
        });
    }

    public function reverseAccountTransfer(AccountTransfer $at): void
    {
        DB::transaction(function () use ($at) {
            $from = Account::whereKey($at->from_account_id)->lockForUpdate()->firstOrFail();
            $to = Account::whereKey($at->to_account_id)->lockForUpdate()->firstOrFail();

            $from->update(['current_balance' => (float) $from->current_balance + (float) $at->amount]);
            $to->update(['current_balance' => (float) $to->current_balance - (float) $at->amount]);
        });
    }
}
