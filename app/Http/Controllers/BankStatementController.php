<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\BankTransfer;
use App\Models\ShippingOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Rekening Koran per akun — mutasi (approved) + saldo berjalan,
 * meniru buku rekening bank (debet/kredit/saldo).
 */
class BankStatementController extends Controller
{
    private function abortUnlessAllowed(): void
    {
        abort_unless(auth()->user()->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
    }

    public function index(Request $request): View
    {
        $this->abortUnlessAllowed();

        $accounts = Account::aktif()->orderBy('name')->get();

        $account = $request->filled('account_id')
            ? $accounts->firstWhere('id', (int) $request->input('account_id'))
            : null;

        $dari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->input('sampai') ?: now()->format('Y-m-d');

        $statement = $account
            ? $this->statementFor($account, $dari, $sampai)
            : ['rows' => [], 'saldoAwal' => null, 'totalDebet' => 0, 'totalKredit' => 0];

        return view('finance.bank_statement.index', array_merge(
            compact('accounts', 'account', 'dari', 'sampai'),
            $statement
        ));
    }

    public function downloadPdf(Request $request)
    {
        $this->abortUnlessAllowed();

        $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'dari' => ['required', 'date'],
            'sampai' => ['required', 'date', 'after_or_equal:dari'],
        ]);

        $account = Account::findOrFail($request->input('account_id'));
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        $statement = $this->statementFor($account, $dari, $sampai);

        $pdf = Pdf::loadView('finance.bank_statement.pdf', array_merge(
            compact('account', 'dari', 'sampai'),
            $statement
        ))->setPaper('a4');

        return $pdf->download('Rekening-Koran-'.Str::slug($account->name).'-'.$dari.'-'.$sampai.'.pdf');
    }

    private function statementFor(Account $account, string $dari, string $sampai): array
    {
        $from = Carbon::parse($dari)->startOfDay();
        $to = Carbon::parse($sampai)->endOfDay();

        $bts = BankTransfer::with('category')
            ->where('account_id', $account->id)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        // Nama pembeli diambil batch (anti N+1) untuk keterangan
        $buyers = ShippingOrder::whereIn('order_id', $bts->pluck('order_online_id')->filter())
            ->pluck('customer_name', 'order_id');

        $movements = collect();
        foreach ($bts as $bt) {
            $isIn = $bt->type === 'in';
            $buyer = $bt->order_online_id && $buyers->has($bt->order_online_id)
                ? $buyers[$bt->order_online_id]
                : null;
            if (! $buyer && $bt->description && preg_match('/Nama:\s*([^\r\n]+)/i', $bt->description, $m)) {
                $buyer = trim($m[1]);
            }
            $keterangan = $buyer
                ?: ($bt->description
                    ?: ($bt->category?->name
                        ?: ($isIn ? 'PENDAPATAN TRANSFER' : 'PENGELUARAN')));
$movements->push([
                    'date' => $bt->transaction_date->format('Y-m-d'),
                    'keterangan' => $keterangan,
                    'debet' => $isIn ? 0 : (float) $bt->amount,
                    'kredit' => $isIn ? (float) $bt->amount : 0,
                    'order' => $bt->transaction_date->format('Y-m-d').'|'.str_pad((string) $bt->id, 10, '0', STR_PAD_LEFT),
                ]);
        }

        $ats = AccountTransfer::where(function ($q) use ($account) {
            $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id);
        })->whereBetween('transfer_date', [$from, $to])->get();

        foreach ($ats as $at) {
            $isFrom = $at->from_account_id === $account->id;
            $movements->push([
                'date' => $at->transfer_date->format('Y-m-d'),
                'keterangan' => $isFrom
                    ? 'ALIHAN DANA KE '.($at->toAccount?->name ?? '?')
                    : 'ALIHAN DANA DARI '.($at->fromAccount?->name ?? '?'),
                'debet' => $isFrom ? (float) $at->amount : 0,
                'kredit' => $isFrom ? 0 : (float) $at->amount,
                'order' => $at->transfer_date->format('Y-m-d').'|'.str_pad((string) $at->id, 10, '0', STR_PAD_LEFT),
            ]);
        }

        // Saldo awal = saldo sekarang − semua movement (approved) sejak tanggal awal periode
        $sumSince = BankTransfer::where('account_id', $account->id)
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $from)
            ->get()
            ->sum(fn ($bt) => $bt->type === 'in' ? (float) $bt->amount : -(float) $bt->amount);

        $sumSince += AccountTransfer::where(function ($q) use ($account) {
            $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id);
        })->where('transfer_date', '>=', $from)
            ->get()
            ->sum(fn ($at) => $at->from_account_id === $account->id ? -(float) $at->amount : (float) $at->amount);

        $saldoAwal = (float) $account->current_balance - $sumSince;

        $rows = $movements->sortBy('order')->values()->all();
        $totalDebet = array_sum(array_column($rows, 'debet'));
        $totalKredit = array_sum(array_column($rows, 'kredit'));

        return compact('rows', 'saldoAwal', 'totalDebet', 'totalKredit');
    }
}
