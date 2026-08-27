<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\TopUpProposal;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    // ─── Daftar Pembelian (admin/owner/super_admin) ─────────────

    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Purchase::query()
            ->with(['variant.product', 'supplier', 'creator', 'inventory']);

        if ($request->filled('variant_id')) {
            $query->where('product_variant_id', $request->variant_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }
        if ($request->filled('bulan')) {
            $query->where('date', 'like', $request->bulan.'-%');
        }

        // Filter status: admin lihat semua, non-admin lihat yang approved saja
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$user->hasRole(['owner', 'super_admin'])) {
            $query->approved();
        }

        $purchases = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $products = Product::aktif()->with('variants')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $inventories = Inventory::orderBy('name')->get();
        $monthList = Purchase::selectRaw("DATE_FORMAT(date, '%Y-%m') as bulan")
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return view('purchase.index', compact('purchases', 'products', 'suppliers', 'inventories', 'monthList'));
    }

    // ─── Form Ajukan Pembelian (admin/owner/super_admin) ────────

    public function create(): View
    {
        $products = Product::aktif()->with('variants')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $inventories = Inventory::orderBy('name')->get();

        return view('purchase.create', compact('products', 'suppliers', 'inventories'));
    }

    // ─── Simpan Pengajuan (status = pending) ────────────────────

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'inventory_id' => ['required', 'exists:inventories,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $data['quantity'] = (int) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['shipping_cost'] = (float) ($data['shipping_cost'] ?? 0);
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';

        $purchase = Purchase::create($data);

        // Notify keuangan/owner/super_admin
        $this->notifyRole(
            ['owner', 'super_admin', 'admin', 'keuangan'],
            'new_purchase_proposal',
            '📥 Pengajuan Pembelian Baru',
            auth()->user()->display_name.' mengajukan pembelian '
                .$purchase->variant->product->name.' (qty '.$purchase->quantity.') '
                .'Rp '.number_format($purchase->quantity * $purchase->unit_price + $purchase->shipping_cost, 0, ',', '.'),
            ['url' => route('approval.index')],
            auth()->id()
        );

        return redirect()->route('approval.index')
            ->with('success', 'Pengajuan pembelian berhasil dikirim. Menunggu persetujuan.');
    }

    // ─── Approval Index (unified: top-up + purchases) ───────────

    public function approvalIndex(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['super_admin', 'keuangan']), 403);

        // ── Top Up Proposals ──
        if ($user->hasRole(['super_admin', 'keuangan'])) {
            $advertisers = User::role('advertiser')->orderBy('nama')->get(['id', 'nama', 'panggilan', 'email', 'avatar']);
            $activeTab = $request->input('tab', 'all');

            $topUpQuery = TopUpProposal::with('user', 'approver', 'items.whitelist');
            if ($activeTab !== 'all') {
                $topUpQuery->where('user_id', $activeTab);
            }
            $topUpProposals = $topUpQuery->latest()->paginate(15, ['*'], 'topup_page');

            // Batch summary per advertiser
            $summaryPerAdv = [];
            if ($advertisers->isNotEmpty()) {
                $batchSummary = TopUpProposal::whereIn('user_id', $advertisers->pluck('id'))
                    ->selectRaw("user_id,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        COALESCE(SUM(total_nominal), 0) as total_nominal")
                    ->groupBy('user_id')
                    ->get()
                    ->keyBy('user_id');

                foreach ($advertisers as $adv) {
                    $s = $batchSummary->get($adv->id);
                    $summaryPerAdv[$adv->id] = [
                        'total' => $s ? (int) $s->total : 0,
                        'pending' => $s ? (int) $s->pending : 0,
                        'completed' => $s ? (int) $s->completed : 0,
                        'total_nominal' => $s ? (float) $s->total_nominal : 0,
                    ];
                }
            }
        } else {
            $advertisers = collect();
            $activeTab = 'all';
            $topUpProposals = TopUpProposal::with('approver', 'items.whitelist')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15, ['*'], 'topup_page');
            $summaryPerAdv = [];
        }

        // ── Purchase Proposals ──
        $purchaseStatus = $request->input('purchase_status');
        $purchaseQuery = Purchase::with(['variant.product', 'supplier', 'creator', 'inventory'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($purchaseStatus) {
            $purchaseQuery->where('status', $purchaseStatus);
        }

        $purchaseProposals = $purchaseQuery->paginate(20, ['*'], 'purchase_page');

        return view('approval.index', compact(
            'topUpProposals', 'advertisers', 'activeTab', 'summaryPerAdv',
            'purchaseProposals', 'purchaseStatus'
        ));
    }

    // ─── Approve Purchase ───────────────────────────────────────

    public function approvePurchase(Purchase $purchase): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
        abort_unless($purchase->isPending(), 400, 'Pembelian sudah diproses.');

        DB::transaction(function () use ($purchase, $user) {
            $purchase->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Record stock + update HPP
            $variant = $purchase->variant;
            $product = $variant->product;
            $hpp = $this->stock->hppRataRata($product, $purchase->quantity, $purchase->unit_price, $purchase->shipping_cost);
            $product->update(['purchase_price' => $hpp]);

            $this->stock->recordIn(
                $variant->id,
                $purchase->date->format('Y-m-d'),
                $purchase->quantity,
                $purchase->unit_price,
                'purchase',
                $purchase->id,
                'Pembelian '.($purchase->supplier?->nama_supplier ?? '-').' → '.($purchase->inventory?->name ?? '-'),
                $user->id,
                (int) $purchase->inventory_id,
            );
        });

        // Notify creator
        $this->notifyUser(
            $purchase->created_by,
            'purchase_approved',
            '✅ Pengajuan Pembelian Disetujui',
            'Pengajuan pembelian Rp '.number_format($purchase->quantity * $purchase->unit_price + $purchase->shipping_cost, 0, ',', '.').' telah disetujui oleh '.$user->display_name.'. Stok sudah masuk.',
            ['url' => route('purchase.index')],
            $user->id
        );

        return redirect()->route('approval.index')
            ->with('success', 'Pengajuan pembelian disetujui. Stok & HPP diperbarui.');
    }

    // ─── Reject Purchase ────────────────────────────────────────

    public function rejectPurchase(Request $request, Purchase $purchase): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
        abort_unless($purchase->isPending(), 400, 'Pembelian sudah diproses.');

        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ]);

        $purchase->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]);

        // Notify creator
        $this->notifyUser(
            $purchase->created_by,
            'purchase_rejected',
            '❌ Pengajuan Pembelian Ditolak',
            'Pengajuan pembelian Rp '.number_format($purchase->quantity * $purchase->unit_price + $purchase->shipping_cost, 0, ',', '.')." ditolak oleh {$user->display_name}. Alasan: {$data['rejection_note']}",
            ['url' => route('approval.index')],
            $user->id
        );

        return redirect()->route('approval.index')
            ->with('success', 'Pengajuan pembelian ditolak.');
    }

    // ─── Hapus (hanya pending) ──────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $purchase = Purchase::findOrFail($id);

        if ($purchase->isPending()) {
            // Pending: cukup hapus (belum ada stok)
            $purchase->delete();

            return redirect()->route('approval.index')
                ->with('success', 'Pengajuan pembelian dihapus.');
        }

        // Approved: balikin stok
        $productId = $purchase->variant?->product_id;

        $this->stock->reverseReference('purchase', $purchase->id);
        $purchase->delete();

        if ($productId) {
            $this->stock->recalculateHpp($productId);
        }

        return redirect()->route('purchase.index')
            ->with('success', 'Pembelian dihapus. Stok & HPP dikembalikan.');
    }

    // ─── Helper: Notifikasi ────────────────────────────────────

    private function notifyRole(array $roles, string $type, string $title, string $message, array $data, ?int $fromUserId): void
    {
        $userIds = User::role($roles)->pluck('id');
        foreach ($userIds as $uid) {
            Notification::create([
                'user_id' => $uid,
                'from_user_id' => $fromUserId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
    }

    private function notifyUser(int $userId, string $type, string $title, string $message, array $data, ?int $fromUserId): void
    {
        Notification::create([
            'user_id' => $userId,
            'from_user_id' => $fromUserId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
