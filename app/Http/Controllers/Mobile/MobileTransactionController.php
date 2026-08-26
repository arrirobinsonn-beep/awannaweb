<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BankTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API mobile untuk transaksi (bank_transfers).
 * Account di-resolve dari mobile device token, BUKAN dari request body.
 */
class MobileTransactionController extends Controller
{
    /**
     * GET /api/mobile/transactions
     * List transaksi milik account yang terhubung dengan device ini.
     */
    public function index(Request $request): JsonResponse
    {
        $account = $request->attributes->get('mobile_account');

        $transactions = BankTransfer::where('account_id', $account->id)
            ->with('category:id,name,type')
            ->orderByDesc('transaction_date')
            ->paginate(15);

        return response()->json($transactions);
    }

    /**
     * GET /api/mobile/transactions/{id}
     * Detail transaksi — pastikan milik account yang sama.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = $request->attributes->get('mobile_account');

        $transaction = BankTransfer::where('id', $id)
            ->where('account_id', $account->id)
            ->with('category:id,name,type')
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan atau bukan milik akun ini.'], 404);
        }

        return response()->json(['data' => $transaction]);
    }

    /**
     * POST /api/mobile/transactions/{id}/confirm
     * Konfirmasi transaksi (pending → confirmed).
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $account = $request->attributes->get('mobile_account');

        $transaction = BankTransfer::where('id', $id)
            ->where('account_id', $account->id)
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan atau bukan milik akun ini.'], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Transaksi tidak bisa dikonfirmasi.',
                'current_status' => $transaction->status,
            ], 422);
        }

        $transaction->update([
            'status' => 'confirmed',
            'confirmed_by' => null,
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil dikonfirmasi.',
            'data' => $transaction->fresh('category'),
        ]);
    }
}
