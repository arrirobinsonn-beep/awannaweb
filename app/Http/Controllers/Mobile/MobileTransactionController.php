<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BankTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * API mobile untuk transaksi (bank_transfers).
 * Account di-resolve dari mobile device token, BUKAN dari request body.
 */
class MobileTransactionController extends Controller
{
    /** Kolom yang di-select dari DB (hindari SELECT *). */
    private const LIST_FIELDS = ['id', 'amount', 'transaction_date', 'description', 'created_by', 'product_id', 'image_url', 'status'];

    /**
     * GET /api/mobile/transactions
     * List transaksi milik account — hanya kolom inti + relasi creator & product.
     */
    public function index(Request $request): JsonResponse
    {
        $account = $request->attributes->get('mobile_account');

        $transactions = BankTransfer::where('account_id', $account->id)
            ->select(self::LIST_FIELDS)
            ->with([
                'creator:id,nama',
                'product:id,name',
            ])
            ->orderByDesc('transaction_date')
            ->paginate(15);

        $mapped = $transactions->getCollection()->map(fn ($t) => [
            'id' => $t->id,
            'amount' => $t->amount,
            'user_name' => $t->creator?->nama ?? '-',
            'transaction_date' => $t->transaction_date->format('Y-m-d H:i'),
            'description' => $t->description,
            'product_name' => $t->product?->name ?? '-',
            'image_url' => $t->image_url ? route('mobile.transactions.image', $t->id) : null,
            'status' => $t->status,
        ]);

        return response()->json([
            'status' => 'success',
            'datas' => $mapped,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * GET /api/mobile/transactions/{id}
     * Detail transaksi — field sama dengan index.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = $request->attributes->get('mobile_account');

        $transaction = BankTransfer::where('id', $id)
            ->where('account_id', $account->id)
            ->select(self::LIST_FIELDS)
            ->with([
                'creator:id,nama',
                'product:id,name',
            ])
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan atau bukan milik akun ini.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'user_name' => $transaction->creator?->nama ?? '-',
                'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i'),
                'description' => $transaction->description,
                'product_name' => $transaction->product?->name ?? '-',
                'image_url' => $transaction->image_url ? route('mobile.transactions.image', $transaction->id) : null,
                'status' => $transaction->status,
            ],
        ]);
    }

    /**
     * GET /api/mobile/transactions/{id}/image
     * Serve image bukti transfer (WebP preferred) — storage tetap private.
     */
    public function image(Request $request, int $id)
    {
        $account = $request->attributes->get('mobile_account');

        $transaction = BankTransfer::where('id', $id)
            ->where('account_id', $account->id)
            ->first();

        if (! $transaction || ! $transaction->image_url) {
            abort(404);
        }

        $url = $transaction->image_url;
        $webpPath = preg_replace('/\.[\w]+$/', '.webp', $url);

        // Prefer WebP, try local disk first (new uploads), then public (old uploads)
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($webpPath)) {
                return response()->file(Storage::disk($disk)->path($webpPath));
            }
            if (Storage::disk($disk)->exists($url)) {
                return response()->file(Storage::disk($disk)->path($url));
            }
        }

        abort(404);
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
