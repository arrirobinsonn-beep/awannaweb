<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master akun keuangan (sumber uang perusahaan). Hanya role keuangan/
 * owner/super_admin/admin. Akun yang sudah punya transaksi tidak bisa
 * dihapus (di-guard manual; FK RESTRICT sebagai pengaman terakhir).
 */
class FinanceAccountController extends Controller
{
    private function abortUnlessAllowed(): void
    {
        abort_unless(auth()->user()->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
    }

    public function index(): View
    {
        $this->abortUnlessAllowed();

        $accounts = Account::withCount(['bankTransfers', 'transfersFrom', 'transfersTo'])
            ->orderBy('status')
            ->orderBy('name')
            ->get();

        $totalBalance = $accounts->sum(fn ($a) => (float) $a->current_balance);

        return view('finance.accounts.index', compact('accounts', 'totalBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', Account::TYPES)],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', Account::STATUSES)],
        ]);
        $data['status'] = $data['status'] ?? 'active';

        Account::create($data);

        return redirect()->route('finance.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', Account::TYPES)],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', Account::STATUSES)],
        ]);
        $data['status'] = $data['status'] ?? 'active';

        $account->update($data);

        return redirect()->route('finance.accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $inUse = $account->bankTransfers()->exists()
            || $account->transfersFrom()->exists()
            || $account->transfersTo()->exists();

        if ($inUse) {
            return back()->withErrors(['account' => 'Akun masih punya transaksi, tidak bisa dihapus.']);
        }

        $account->delete();

        return redirect()->route('finance.accounts.index')->with('success', 'Akun berhasil dihapus.');
    }

    public function toggle(Account $account): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $account->update(['status' => $account->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', $account->status === 'active'
            ? 'Akun diaktifkan.'
            : 'Akun dinonaktifkan.');
    }
}
