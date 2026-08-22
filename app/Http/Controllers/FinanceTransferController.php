<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Illuminate\View\View;

/**
 * Operan saldo antar akun (from → to). Saldo kedua akun diupdate
 * otomatis via FinanceService; validasi saldo cukup.
 */
class FinanceTransferController extends Controller
{
    private function abortUnlessAllowed(): void
    {
        abort_unless(auth()->user()->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
    }

    public function index(): View
    {
        $this->abortUnlessAllowed();

        $transfers = AccountTransfer::with('fromAccount', 'toAccount', 'creator')
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(20);

        $accounts = Account::aktif()->orderBy('name')->get();

        return view('finance.transfers.index', compact('transfers', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:5000'],
            'transfer_date' => ['required', 'date'],
        ]);

        try {
            $transfer = DB::transaction(function () use ($data) {
                $transfer = AccountTransfer::create([
                    ...$data,
                    'created_by' => auth()->id(),
                ]);

                app(FinanceService::class)->applyAccountTransfer($transfer);

                return $transfer;
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('finance.transfers.index')
            ->with('success', 'Transfer antar akun berhasil dicatat (Rp '.number_format((float) $transfer->amount, 0, ',', '.').').');
    }

    public function destroy(AccountTransfer $transfer): RedirectResponse
    {
        $this->abortUnlessAllowed();

        DB::transaction(function () use ($transfer) {
            app(FinanceService::class)->reverseAccountTransfer($transfer);
            $transfer->delete();
        });

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer berhasil dihapus & saldo dikembalikan.');
    }
}
