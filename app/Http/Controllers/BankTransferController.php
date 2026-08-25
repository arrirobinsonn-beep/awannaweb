<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ShippingOrder;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Bukti transfer (bank_transfers).
 *
 * Alur: CS upload → pending → pemilik bank confirm → confirmed → guru approve → approved.
 * Gambar tidak pernah dihapus otomatis — hanya manual oleh guru via deleteImage().
 */
class BankTransferController extends Controller
{
    public const APPROVERS = ['owner', 'super_admin', 'admin', 'keuangan'];

    private function isApprover(): bool
    {
        return auth()->user()->hasRole(self::APPROVERS);
    }

    /** Cek apakah user adalah pemilik dari akun tertentu (via pivot account_owners) */
    private function isAccountOwner(Account $account): bool
    {
        return $account->owners()->where('users.id', auth()->id())->exists();
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $isApprover = $this->isApprover();

        $query = BankTransfer::with('account', 'category', 'creator', 'product', 'confirmer');

        if (! $isApprover && ! $user->hasRole('pemilik_bank')) {
            abort_unless($user->hasRole('cs'), 403);
            $query->where('created_by', $user->id);
        }

        // Pemilik bank hanya lihat bukti transfer ke akun yang dia miliki
        if ($user->hasRole('pemilik_bank') && ! $isApprover) {
            $ownedAccountIds = $user->ownedAccounts()->pluck('accounts.id');
            $query->whereIn('account_id', $ownedAccountIds);
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            $s = trim($request->input('search'));
            $q->where(function ($qq) use ($s) {
                $qq->where('description', 'like', "%{$s}%")
                    ->orWhere('order_online_id', 'like', "%{$s}%")
                    ->orWhereHas('account', fn ($a) => $a->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('product', fn ($p) => $p->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))
                    ->orWhereHas('creator', fn ($u) => $u->where('nama', 'like', "%{$s}%"));
            });
        })->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->input('account_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')));

        $transfers = $query->latest('transaction_date')->latest('id')->paginate(20);

        $stats = (clone $query)->reorder()
            ->selectRaw('COUNT(*) as total,
                COALESCE(SUM(CASE WHEN type = "in" THEN amount END), 0) as masuk,
                COALESCE(SUM(CASE WHEN type = "out" THEN amount END), 0) as keluar,
                COALESCE(SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END), 0) as pending')
            ->first();

        $statusCounts = (clone $query)->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $accounts = Account::aktif()->orderBy('name')->get();
        $categories = TransactionCategory::orderBy('type')->orderBy('name')->get();
        $products = Product::aktif()->orderBy('code')->get(['id', 'code', 'name']);
        $orderIds = ShippingOrder::whereNotNull('order_id')
            ->where('order_id', '!=', '')
            ->distinct()
            ->orderByDesc('id')
            ->limit(500)
            ->pluck('order_id');

        // ID akun yang dimiliki user ini (untuk tampilkan tombol confirm di view)
        $ownedAccountIds = $user->ownedAccounts()->pluck('accounts.id')->toArray();

        return view('finance.bank_transfers.index', compact(
            'transfers', 'accounts', 'categories', 'products', 'orderIds',
            'stats', 'statusCounts', 'isApprover', 'ownedAccountIds'
        ));
    }

    public function pendingCount(): JsonResponse
    {
        $user = auth()->user();

        if ($user->hasRole(self::APPROVERS)) {
            $count = BankTransfer::where('status', 'pending')->count();
        } elseif ($user->hasRole('cs')) {
            $count = BankTransfer::where('status', 'pending')->where('created_by', $user->id)->count();
        } else {
            abort(403);
        }

        return response()->json(['count' => $count]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isApprover = $this->isApprover();
        $isCs = $user->hasRole('cs');

        abort_unless($isApprover || $isCs, 403);

        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'order_online_id' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', BankTransfer::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:5000'],
            'transaction_date' => ['required', 'date'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        if ($isCs) {
            abort_unless($data['type'] === 'in', 403, 'CS hanya bisa meng-upload bukti transfer MASUK.');
            abort_unless($request->hasFile('image'), 422, 'Bukti transfer (gambar) wajib di-upload.');
        }

        $bt = DB::transaction(function () use ($request, $data, $user, $isApprover) {
            $bt = BankTransfer::create([
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'product_id' => $data['product_id'] ?? null,
                'order_online_id' => $data['order_online_id'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_by' => $user->id,
                'image_url' => $request->hasFile('image')
                    ? $request->file('image')->store('bukti-transfer', 'public')
                    : null,
                // Approver = langsung dicatat (saldo ter-update); CS = pending (perlu confirm pemilik bank dulu).
                'status' => $isApprover ? 'approved' : 'pending',
            ]);

            app(FinanceService::class)->applyBankTransfer($bt);

            return $bt;
        });

        if ($isCs) {
            // Notifikasi ke semua approver
            foreach (User::role(self::APPROVERS)->pluck('id') as $uid) {
                Notification::create([
                    'user_id' => $uid,
                    'from_user_id' => $user->id,
                    'type' => 'bank_transfer_received',
                    'title' => '📥 Bukti Transfer Baru',
                    'message' => $user->display_name.' meng-upload bukti transfer Rp '
                        .number_format((float) $data['amount'], 0, ',', '.')
                        .' ke '.$bt->account->name.'. Klik untuk memeriksa & persetujuan.',
                    'data' => ['url' => route('finance.bank-transfers.index')],
                ]);
            }

            // Notifikasi ke pemilik bank yang memiliki akun ini
            $account = $bt->account->load('owners');
            foreach ($account->owners as $owner) {
                Notification::create([
                    'user_id' => $owner->id,
                    'from_user_id' => $user->id,
                    'type' => 'bank_transfer_received',
                    'title' => '📥 Bukti Transfer Masuk',
                    'message' => $user->display_name.' meng-upload bukti transfer Rp '
                        .number_format((float) $data['amount'], 0, ',', '.')
                        .' ke '.$bt->account->name.'. Silakan tandai jika sudah masuk ke rekening Anda.',
                    'data' => ['url' => route('finance.bank-transfers.index')],
                ]);
            }
        }

        return redirect()->route('finance.bank-transfers.index')
            ->with('success', $isApprover
                ? 'Transaksi berhasil dicatat (saldo otomatis diperbarui).'
                : 'Bukti transfer terkirim. Menunggu konfirmasi pemilik bank.');
    }

    /**
     * Pemilik bank menandai bukti transfer sudah masuk ke rekening.
     * Status: pending → confirmed. Tidak hapus gambar, tidak ubah saldo.
     */
    public function confirm(BankTransfer $bankTransfer): RedirectResponse
    {
        abort_unless($bankTransfer->isPending(), 400, 'Transaksi sudah diproses.');
        abort_unless($this->isAccountOwner($bankTransfer->account), 403, 'Anda bukan pemilik akun ini.');

        $bankTransfer->update([
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        // Notifikasi ke semua approver bahwa pemilik bank sudah confirm
        foreach (User::role(self::APPROVERS)->pluck('id') as $uid) {
            Notification::create([
                'user_id' => $uid,
                'from_user_id' => auth()->id(),
                'type' => 'bank_transfer_confirmed',
                'title' => '✅ Bukti Transfer Dikonfirmasi Pemilik Bank',
                'message' => auth()->user()->display_name.' menandai bukti transfer Rp '
                    .number_format((float) $bankTransfer->amount, 0, ',', '.')
                    .' ke '.$bankTransfer->account->name.' sudah masuk ke rekening.',
                'data' => ['url' => route('finance.bank-transfers.index')],
            ]);
        }

        return redirect()->route('finance.bank-transfers.index')
            ->with('success', 'Bukti transfer ditandai sudah masuk. Menunggu persetujuan guru.');
    }

    public function approve(BankTransfer $bankTransfer): RedirectResponse
    {
        abort_unless($this->isApprover(), 403);
        abort_unless($bankTransfer->isConfirmed(), 400, 'Bukti transfer harus dikonfirmasi pemilik bank terlebih dahulu.');

        DB::transaction(function () use ($bankTransfer) {
            $bankTransfer->update(['status' => 'approved']);
            app(FinanceService::class)->applyBankTransfer($bankTransfer);
        });

        $this->notifyCreator($bankTransfer, 'bank_transfer_approved',
            '✅ Bukti Transfer Disetujui',
            'Bukti transfer Rp '.number_format((float) $bankTransfer->amount, 0, ',', '.')
            .' ke '.$bankTransfer->account->name.' telah disetujui.',
            auth()->id());

        return redirect()->route('finance.bank-transfers.index')->with('success', 'Transaksi disetujui. Saldo akun diperbarui.');
    }

    public function reject(Request $request, BankTransfer $bankTransfer): RedirectResponse
    {
        abort_unless($this->isApprover(), 403);
        abort_unless(in_array($bankTransfer->status, ['pending', 'confirmed']), 400, 'Transaksi sudah diproses.');

        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ]);

        $bankTransfer->update([
            'status' => 'rejected',
            'rejection_note' => $data['rejection_note'],
        ]);

        $this->notifyCreator($bankTransfer, 'bank_transfer_rejected',
            '❌ Bukti Transfer Ditolak',
            'Bukti transfer Rp '.number_format((float) $bankTransfer->amount, 0, ',', '.')
            .' ditolak. Alasan: '.$data['rejection_note'],
            auth()->id());

        return redirect()->route('finance.bank-transfers.index')->with('success', 'Transaksi ditolak. Feedback terkirim ke CS.');
    }

    /**
     * Hapus gambar bukti transfer secara manual (guru/keuangan).
     * Tidak mengubah status atau saldo.
     */
    public function deleteImage(BankTransfer $bankTransfer): RedirectResponse
    {
        abort_unless($this->isApprover(), 403);
        abort_unless($bankTransfer->image_url, 404, 'Tidak ada gambar yang bisa dihapus.');

        if (Storage::disk('public')->exists($bankTransfer->image_url)) {
            Storage::disk('public')->delete($bankTransfer->image_url);
        }

        $bankTransfer->update(['image_url' => null]);

        return redirect()->route('finance.bank-transfers.index')
            ->with('success', 'Gambar bukti transfer berhasil dihapus.');
    }

    public function download(BankTransfer $bankTransfer)
    {
        abort_unless($this->isApprover() || $this->isAccountOwner($bankTransfer->account) || $bankTransfer->created_by === auth()->id(), 403);
        abort_unless($bankTransfer->image_url && Storage::disk('public')->exists($bankTransfer->image_url),
            404, 'Bukti gambar sudah tidak tersedia.');

        $buyer = $bankTransfer->order_online_id
            ? optional(ShippingOrder::where('order_id', $bankTransfer->order_online_id)->first())->customer_name
            : null;
        $cs = $bankTransfer->creator?->display_name;

        $slug = implode('-', array_filter([
            $buyer ? Str::slug($buyer) : null,
            $cs ? Str::slug($cs) : null,
            $bankTransfer->id,
        ]));

        return Storage::disk('public')->download(
            $bankTransfer->image_url,
            'bukti-'.$slug.'-'.date('Ymd').'.'.pathinfo($bankTransfer->image_url, PATHINFO_EXTENSION)
        );
    }

    public function destroy(BankTransfer $bankTransfer): RedirectResponse
    {
        abort_unless($this->isApprover(), 403);

        $wasApproved = $bankTransfer->isApproved();

        DB::transaction(function () use ($bankTransfer) {
            app(FinanceService::class)->reverseBankTransfer($bankTransfer);

            if ($bankTransfer->image_url) {
                Storage::disk('public')->delete($bankTransfer->image_url);
            }

            $bankTransfer->delete();
        });

        return redirect()->route('finance.bank-transfers.index')->with('success',
            $wasApproved
                ? 'Transaksi dihapus & saldo dikembalikan.'
                : 'Transaksi dihapus (saldo belum pernah berubah karena statusnya belum disetujui).');
    }

    private function notifyCreator(BankTransfer $bt, string $type, string $title, string $message, ?int $fromUserId): void
    {
        if (! $bt->created_by) {
            return;
        }

        Notification::create([
            'user_id' => $bt->created_by,
            'from_user_id' => $fromUserId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => ['url' => route('finance.bank-transfers.index')],
        ]);
    }
}
