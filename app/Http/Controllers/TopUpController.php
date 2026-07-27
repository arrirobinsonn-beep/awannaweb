<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\TopUpProposal;
use App\Models\TopUpProposalItem;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TopUpController extends Controller
{
    // ─── Daftar Pengajuan ──────────────────────────────────────

    public function index(Request $request): View
    {
        $user = Auth::user();

        if ($user->hasRole(['owner', 'super_admin', 'admin', 'keuangan'])) {
            // Super admin: tab per advertiser
            $advertisers = User::role('advertiser')
                ->orderBy('nama')
                ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

            $activeTab = $request->input('tab', 'all');

            $proposalsQuery = TopUpProposal::with('user', 'approver', 'items.whitelist');

            if ($activeTab !== 'all') {
                $proposalsQuery->where('user_id', $activeTab);
            }

            $proposals = $proposalsQuery->latest()->paginate(15);

            // ─── BATCH: Data summary per advertiser (1 query, no loop) ──
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

            return view('topup.index', compact('proposals', 'advertisers', 'activeTab', 'summaryPerAdv'));
        } else {
            // Advertiser: hanya lihat pengajuan sendiri
            $proposals = TopUpProposal::with('approver', 'items.whitelist')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15);

            return view('topup.index', compact('proposals'));
        }
    }

    // ─── Form Buat Pengajuan ───────────────────────────────────

    public function create(): View
    {
        $user = Auth::user();

        // Whitelist milik advertiser yg aktif
        $whitelists = Whitelist::where('user_id', $user->id)
            ->aktif()
            ->get(['id', 'nama', 'kode', 'platform', 'total_topup', 'total_spending']);

        // Cek apakah ada pengajuan sebelumnya (untuk info top up sebelumnya)
        $lastProposal = TopUpProposal::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->latest()
            ->first();

        $previousTopupTotal = $lastProposal?->total_nominal ?? 0;

        return view('topup.create', compact(
            'whitelists', 'previousTopupTotal'
        ));
    }

    // ─── Simpan Pengajuan ──────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.whitelist_id' => ['required', 'exists:whitelists,id'],
            'items.*.nominal' => ['required', 'numeric', 'min:0'],
            'today_spending' => ['required', 'numeric', 'min:0'],
            'today_lead' => ['required', 'integer', 'min:0'],
            'today_paid' => ['required', 'integer', 'min:0'],
        ]);

        // Validasi: whitelist harus milik advertiser ini
        $wlIds = collect($data['items'])->pluck('whitelist_id');
        $validWl = Whitelist::whereIn('id', $wlIds)
            ->where('user_id', $user->id)
            ->pluck('id');

        foreach ($wlIds as $wlId) {
            abort_unless($validWl->contains($wlId), 403, 'Whitelist bukan milik Anda.');
        }

        $totalNominal = collect($data['items'])->sum('nominal');

        $proposal = DB::transaction(function () use ($user, $data, $totalNominal) {
            $proposal = TopUpProposal::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'previous_topup_total' => $user->topUpProposals()
                    ->whereIn('status', ['approved', 'completed'])
                    ->latest()
                    ->first()?->total_nominal ?? 0,
                'today_lead' => (int) $data['today_lead'],
                'today_paid' => (int) $data['today_paid'],
                'today_spending' => (float) $data['today_spending'],
                'total_nominal' => $totalNominal,
            ]);

            foreach ($data['items'] as $item) {
                TopUpProposalItem::create([
                    'proposal_id' => $proposal->id,
                    'whitelist_id' => $item['whitelist_id'],
                    'nominal' => $item['nominal'],
                    'payment_status' => 'pending',
                ]);
            }

            return $proposal;
        });

        // Notifikasi ke semua super admin / owner / admin
        $this->notifyRole(
            ['owner', 'super_admin', 'admin', 'keuangan'],
            'new_proposal',
            '💰 Pengajuan Top Up Baru',
            "{$user->display_name} mengajukan top up Rp ".number_format($totalNominal, 0, ',', '.'),
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $user->id
        );

        return redirect()->route('topup.index')
            ->with('success', 'Pengajuan top up berhasil dikirim. Menunggu persetujuan Super Admin.');
    }

    // ─── Detail Pengajuan ──────────────────────────────────────

    public function show(TopUpProposal $proposal): View
    {
        $user = Auth::user();

        // Advertiser hanya bisa lihat miliknya
        // Keuangan & admin bisa lihat semua
        if ($user->hasRole('advertiser') && $proposal->user_id !== $user->id) {
            abort(403);
        }

        $proposal->load('user', 'approver', 'items.whitelist');

        return view('topup.show', compact('proposal'));
    }

    // ─── Approve ───────────────────────────────────────────────

    public function approve(TopUpProposal $proposal): RedirectResponse
    {
        $approver = Auth::user();
        abort_unless($approver->hasRole(['owner', 'super_admin', 'admin']), 403);
        abort_unless($proposal->isPending(), 400, 'Proposal sudah diproses.');

        $proposal->update([
            'status' => 'approved',
            'approver_id' => $approver->id,
            'approved_at' => now(),
        ]);

        // Notifikasi ke advertiser
        $this->notifyUser(
            $proposal->user_id,
            'proposal_approved',
            '✅ Pengajuan Top Up Disetujui',
            'Pengajuan top up Rp '.number_format($proposal->total_nominal, 0, ',', '.')." telah disetujui oleh {$approver->display_name}. Silakan lanjut ke pembayaran.",
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $approver->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Pengajuan top up disetujui. Silakan lanjut ke pembayaran.');
    }

    // ─── Decline ───────────────────────────────────────────────

    public function decline(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $approver = Auth::user();
        abort_unless($approver->hasRole(['owner', 'super_admin', 'admin']), 403);
        abort_unless($proposal->isPending(), 400, 'Proposal sudah diproses.');

        $data = $request->validate([
            'decline_note' => ['required', 'string', 'max:500'],
        ]);

        $proposal->update([
            'status' => 'declined',
            'approver_id' => $approver->id,
            'decline_note' => $data['decline_note'],
            'declined_at' => now(),
        ]);

        // Notifikasi ke advertiser
        $this->notifyUser(
            $proposal->user_id,
            'proposal_declined',
            '❌ Pengajuan Top Up Ditolak',
            'Pengajuan top up Rp '.number_format($proposal->total_nominal, 0, ',', '.')." ditolak oleh {$approver->display_name}. Alasan: {$data['decline_note']}",
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $approver->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Pengajuan top up ditolak.');
    }

    // ─── Form Pembayaran (Advertiser input VA) ─────────────────

    public function paymentForm(TopUpProposal $proposal): View
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isApproved(), 400, 'Pengajuan belum disetujui.');

        $proposal->load('items.whitelist');

        return view('topup.payment', compact('proposal'));
    }

    // ─── Simpan Pembayaran VA ──────────────────────────────────

    public function paymentStore(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isApproved(), 400, 'Pengajuan belum disetujui.');

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.item_id' => ['required', 'exists:top_up_proposal_items,id'],
            'items.*.va_number' => ['required', 'string', 'max:100'],
        ]);

        $allVaSubmitted = false;

        DB::transaction(function () use ($proposal, $data, &$allVaSubmitted) {
            foreach ($data['items'] as $itemData) {
                $item = TopUpProposalItem::findOrFail($itemData['item_id']);
                abort_unless($item->proposal_id === $proposal->id, 403);
                abort_unless($item->isPending(), 400, 'Item sudah dibayar.');

                $item->update([
                    'va_number' => $itemData['va_number'],
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Update whitelist
                $wl = $item->whitelist;
                $wl->total_topup += (float) $item->nominal;
                $wl->nominal_terakhir_topup = (float) $item->nominal;
                $wl->save();
            }

            // Set status ke 'menunggu_pembayaran' hanya jika semua item sudah diisi VA
            $pendingCount = $proposal->items()->where('payment_status', 'pending')->count();
            if ($pendingCount === 0) {
                $proposal->update(['status' => 'menunggu_pembayaran']);
                $allVaSubmitted = true;
            }
        });

        // Notifikasi ke super admin: VA tersedia untuk disalin
        if ($allVaSubmitted) {
            $this->notifyRole(
                ['owner', 'super_admin', 'admin', 'keuangan'],
                'va_submitted',
                '🏦 VA Top Up Tersedia',
                "{$user->display_name} telah mencatat nomor VA untuk top up Rp ".number_format($proposal->total_nominal, 0, ',', '.').'. Silakan cek dan salin VA.',
                ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
                $user->id
            );
        }

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Nomor VA berhasil dicatat. Status pengajuan: Menunggu Pembayaran VA. Super Admin akan memproses pembayaran.');
    }

    // ─── Super Admin: Tandai VA Sudah Dibayar ─────────────────

    public function markVaPaid(TopUpProposal $proposal): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
        abort_unless($proposal->isMenungguPembayaran(), 400, 'Pengajuan belum dalam tahap pembayaran VA.');
        abort_if($proposal->isVaPaid(), 400, 'VA sudah ditandai dibayar.');

        $proposal->update([
            'va_paid_at' => now(),
            'va_paid_by' => $user->id,
        ]);

        // Notifikasi ke advertiser: VA sudah dibayar, silakan input sisa saldo
        $this->notifyUser(
            $proposal->user_id,
            'va_paid',
            '✅ Top Up Sudah Dibayarkan!',
            'Top up Rp '.number_format($proposal->total_nominal, 0, ',', '.')." telah dibayarkan oleh {$user->display_name}. Silakan cek sisa saldo whitelist dan laporkan.",
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $user->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'VA berhasil ditandai sudah dibayar. Advertiser telah mendapat notifikasi untuk input sisa saldo.');
    }

    // ─── Form Input Sisa Saldo (Advertiser, setelah VA dibayar) ─

    public function confirmForm(TopUpProposal $proposal): View
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isMenungguPembayaran(), 400, 'Pengajuan belum dalam tahap pembayaran VA.');
        abort_unless($proposal->isVaPaid(), 400, 'VA belum dibayar oleh Super Admin.');

        $proposal->load('items.whitelist');

        return view('topup.confirm', compact('proposal'));
    }

    // ─── Simpan Sisa Saldo (Advertiser) ────────────────────────

    public function confirmStore(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isMenungguPembayaran(), 400, 'Pengajuan belum dalam tahap pembayaran VA.');
        abort_unless($proposal->isVaPaid(), 400, 'VA belum dibayar oleh Super Admin.');

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.item_id' => ['required', 'exists:top_up_proposal_items,id'],
            'items.*.sisa_saldo_dilaporkan' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($proposal, $data) {
            foreach ($data['items'] as $itemData) {
                $item = TopUpProposalItem::findOrFail($itemData['item_id']);
                abort_unless($item->proposal_id === $proposal->id, 403);
                abort_unless($item->isPaid(), 400, 'Item belum dibayar.');
                abort_if($item->sisa_saldo_dilaporkan !== null, 400, 'Item sudah dikonfirmasi.');

                $item->update([
                    'sisa_saldo_dilaporkan' => $itemData['sisa_saldo_dilaporkan'],
                ]);
            }

            $proposal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        });

        // Notifikasi ke super admin
        $this->notifyRole(
            ['owner', 'super_admin', 'admin', 'keuangan'],
            'payment_confirmed',
            '📊 Sisa Saldo Dilaporkan',
            "{$user->display_name} telah melaporkan sisa saldo whitelist untuk top up Rp ".number_format($proposal->total_nominal, 0, ',', '.').'. Proposal selesai.',
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $user->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Sisa saldo berhasil dilaporkan. Status pengajuan: Selesai.');
    }

    // ─── Helper: Notifikasi ────────────────────────────────────

    /**
     * Kirim notifikasi ke semua user dengan role tertentu
     */
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

    /**
     * Kirim notifikasi ke user tertentu
     */
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
