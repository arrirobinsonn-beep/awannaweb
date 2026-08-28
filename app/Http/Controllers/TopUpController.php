<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\Notification;
use App\Models\TransactionCategory;
use App\Models\TopUpPaymentBatch;
use App\Models\TopUpProposalReview;
use App\Models\SpendingHarian;
use App\Models\TopUpProposal;
use App\Models\TopUpProposalItem;
use App\Models\User;
use App\Models\Whitelist;
use App\Services\FinanceService;
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
        abort_if($user->hasRole('keuangan'), 403, 'Gunakan menu Pengajuan / Approval.');

        if ($user->hasRole(['owner', 'super_admin', 'admin'])) {
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

            // Data untuk modal pengajuan (kosong — admin tidak membuat pengajuan)
            $whitelists = collect();
            $sisaSaldoWhitelists = collect();
            $previousTopupTotal = 0;
            $wlDataJson = json_encode([]);

            return view('topup.index', compact('proposals', 'advertisers', 'activeTab', 'summaryPerAdv', 'whitelists', 'sisaSaldoWhitelists', 'previousTopupTotal', 'wlDataJson'));
        } else {
            // Advertiser: hanya lihat pengajuan sendiri
            $proposals = TopUpProposal::with('approver', 'items.whitelist')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15);

            // ── Data untuk modal pengajuan 3-step ──
            $whitelists = Whitelist::where('user_id', $user->id)
                ->aktif()
                ->get(['id', 'nama', 'kode', 'platform', 'total_topup', 'total_spending']);

            // Spending kemarin per whitelist (untuk sisa saldo)
            $kemarin = now()->subDay()->format('Y-m-d');
            $spendingKemarin = SpendingHarian::whereDate('tanggal', $kemarin)
                ->whereIn('whitelist_id', $whitelists->pluck('id'))
                ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
                ->groupBy('whitelist_id')
                ->get()
                ->keyBy('whitelist_id');

            $sisaSaldoWhitelists = $whitelists
                ->filter(fn ($wl) => $spendingKemarin->has($wl->id))
                ->map(function ($wl) use ($spendingKemarin) {
                    $s = $spendingKemarin->get($wl->id);
                    $wl->spending_kemarin = (float) $s->total_spending;
                    $wl->lead_kemarin = (int) $s->total_lead;
                    $wl->paid_kemarin = (int) $s->total_paid;
                    return $wl;
                })
                ->values();

            // Top up sebelumnya
            $lastProposal = TopUpProposal::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->latest()->first();
            $previousTopupTotal = $lastProposal?->total_nominal ?? 0;

            // Data JSON untuk JS modal — encode di controller, hindari @json di Blade
            $wlDataJson = json_encode($whitelists->map(fn ($wl) => [
                'id' => $wl->id, 'nama' => $wl->nama, 'kode' => $wl->kode,
                'platform' => $wl->platform, 'sisa_saldo' => $wl->sisa_saldo ?? 0,
            ])->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

            return view('topup.index', compact(
                'proposals', 'whitelists', 'sisaSaldoWhitelists', 'previousTopupTotal', 'wlDataJson'
            ));
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

        // ─── Whitelist yang melakukan spending kemarin (untuk input sisa saldo) ──
        $kemarin = now()->subDay()->format('Y-m-d');
        $spendingKemarin = SpendingHarian::whereDate('tanggal', $kemarin)
            ->whereIn('whitelist_id', $whitelists->pluck('id'))
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')
            ->get()
            ->keyBy('whitelist_id');

        // Whitelist dengan spending kemarin, diurutkan sesuai urutan whitelists
        $sisaSaldoWhitelists = $whitelists
            ->filter(fn ($wl) => $spendingKemarin->has($wl->id))
            ->map(function ($wl) use ($spendingKemarin) {
                $s = $spendingKemarin->get($wl->id);
                $wl->spending_kemarin = (float) $s->total_spending;
                $wl->lead_kemarin = (int) $s->total_lead;
                $wl->paid_kemarin = (int) $s->total_paid;

                return $wl;
            })
            ->values();

        // Cek apakah ada pengajuan sebelumnya (untuk info top up sebelumnya)
        $lastProposal = TopUpProposal::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->latest()
            ->first();

        $previousTopupTotal = $lastProposal?->total_nominal ?? 0;

        return view('topup.create', compact(
            'whitelists', 'sisaSaldoWhitelists', 'previousTopupTotal'
        ));
    }

    // ─── Simpan Pengajuan ──────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Pesan error ramah: sebut nama whitelist, bukan 'items.8.nominal'
        $wlNames = Whitelist::where('user_id', $user->id)
            ->whereIn('id', array_keys($request->input('items', [])))
            ->pluck('nama', 'id');

        $messages = [
            'items.required' => 'Centang minimal satu whitelist yang akan di-top up.',
            'items.min' => 'Centang minimal satu whitelist yang akan di-top up.',
            'items.*.nominal.required' => 'Nominal top up untuk :attribute wajib diisi.',
        ];

        $attributes = [];
        foreach ($wlNames as $wlId => $nama) {
            $attributes['items.'.$wlId.'.nominal'] = $nama.' (Rp)';
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.whitelist_id' => ['required', 'exists:whitelists,id'],
            'items.*.nominal' => ['required', 'numeric', 'min:0'],
            'today_spending' => ['required', 'numeric', 'min:0'],
            'today_lead' => ['required', 'integer', 'min:0'],
            'today_paid' => ['required', 'integer', 'min:0'],
        ], $messages, $attributes);

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

        $proposal->load([
            'user',
            'approver',
            'items.whitelist',
            'paymentBatches.items.whitelist',
            'reviews.reviewer',
        ]);

        return view('topup.show', compact('proposal'));
    }

    // ─── Approve ───────────────────────────────────────────────

    public function approve(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $approver = Auth::user();
        abort_unless($approver->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
        abort_unless($proposal->isPending(), 400, 'Proposal sudah diproses.');

        $data = $request->validate([
            'payment_mode' => ['required', 'in:shared_va,single_va_per_wl'],
            'source_account_id' => ['required', 'exists:accounts,id'],
        ]);

        $userName = $proposal->user?->display_name ?? 'Advertiser';
        $description = now()->format('d/m/Y').' - Top Up - '.$userName;

        DB::transaction(function () use ($proposal, $approver, $data, $description) {
            $proposal->update([
                'status' => 'approved',
                'payment_mode' => $data['payment_mode'],
                'approver_id' => $approver->id,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'decline_note' => null,
                'suggested_total_nominal' => null,
                'source_account_id' => $data['source_account_id'],
            ]);

            TopUpProposalReview::create([
                'proposal_id' => $proposal->id,
                'reviewer_id' => $approver->id,
                'decision' => 'approved',
                'note' => 'Disetujui dengan mode '.$data['payment_mode'].'.',
            ]);

            // Buat transaksi keluar (bank_transfer out)
            $category = TransactionCategory::where('name', 'Top Up')->where('type', 'out')->first();
            $bankTransfer = BankTransfer::create([
                'account_id' => $data['source_account_id'],
                'category_id' => $category?->id,
                'type' => 'out',
                'amount' => $proposal->total_nominal,
                'description' => $description,
                'transaction_date' => now(),
                'created_by' => $approver->id,
                'status' => 'approved',
                'source_type' => 'top_up_proposal',
                'source_id' => $proposal->id,
            ]);

            app(FinanceService::class)->applyBankTransfer($bankTransfer);

            $items = $proposal->items()->orderBy('id')->get();
            if ($data['payment_mode'] === 'shared_va') {
                $batch = TopUpPaymentBatch::create([
                    'proposal_id' => $proposal->id,
                    'batch_no' => 1,
                    'payment_mode' => 'shared_va',
                    'nominal' => $items->sum('nominal'),
                ]);
                foreach ($items as $item) {
                    $item->update(['payment_batch_id' => $batch->id, 'approved_nominal' => $item->nominal]);
                }
            } else {
                foreach ($items as $index => $item) {
                    $batch = TopUpPaymentBatch::create([
                        'proposal_id' => $proposal->id,
                        'batch_no' => $index + 1,
                        'payment_mode' => 'single_va_per_wl',
                        'nominal' => $item->nominal,
                    ]);
                    $item->update(['payment_batch_id' => $batch->id, 'approved_nominal' => $item->nominal]);
                }
            }
        });

        $this->notifyUser(
            $proposal->user_id,
            'proposal_approved',
            '✅ Pengajuan Top Up Disetujui',
            'Pengajuan top up Rp '.number_format($proposal->total_nominal, 0, ',', '.')." telah disetujui oleh {$approver->display_name}. Transaksi keluar sudah dicatat.",
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $approver->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Pengajuan disetujui & transaksi keluar Rp '.number_format($proposal->total_nominal, 0, ',', '.').' sudah dicatat.');
    }

    // ─── Decline ───────────────────────────────────────────────

    public function decline(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $approver = Auth::user();
        abort_unless($approver->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
        abort_unless($proposal->isPending(), 400, 'Proposal sudah diproses.');

        $data = $request->validate([
            'decline_note' => ['required', 'string', 'max:500'],
            'suggested_total_nominal' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($proposal, $approver, $data) {
            $proposal->update([
                'status' => 'revision_requested',
                'approver_id' => $approver->id,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'decline_note' => $data['decline_note'],
                'suggested_total_nominal' => $data['suggested_total_nominal'] ?? null,
                'declined_at' => now(),
            ]);

            TopUpProposalReview::create([
                'proposal_id' => $proposal->id,
                'reviewer_id' => $approver->id,
                'decision' => 'revision_requested',
                'suggested_total_nominal' => $data['suggested_total_nominal'] ?? null,
                'note' => $data['decline_note'],
            ]);
        });

        $this->notifyUser(
            $proposal->user_id,
            'proposal_declined',
            '⚠️ Pengajuan Top Up Perlu Revisi',
            'Pengajuan top up Rp '.number_format($proposal->total_nominal, 0, ',', '.')." diminta revisi oleh {$approver->display_name}. Alasan: {$data['decline_note']}",
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $approver->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Pengajuan top up diminta revisi.');
    }

    // ─── Form Pembayaran (Advertiser input VA) ─────────────────
    
    public function revise(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->status === 'revision_requested', 400, 'Pengajuan tidak dalam status revisi.');

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($proposal, $data) {
            $totalNominal = 0;
            foreach ($data['items'] as $itemId => $nominal) {
                $item = $proposal->items()->find($itemId);
                if ($item) {
                    $item->update(['nominal' => $nominal]);
                    $totalNominal += $nominal;
                }
            }
            
            $proposal->update([
                'status' => 'pending',
                'total_nominal' => $totalNominal,
                'decline_note' => null,
            ]);
        });

        $this->notifyRole(
            ['super_admin', 'keuangan'],
            'proposal_revised',
            '📝 Pengajuan Top Up Direvisi',
            "{$user->display_name} merevisi pengajuannya menjadi Rp ".number_format($proposal->total_nominal, 0, ',', '.'),
            ['proposal_id' => $proposal->id, 'url' => route('topup.show', $proposal)],
            $user->id
        );

        return redirect()->route('topup.show', $proposal)
            ->with('success', 'Pengajuan berhasil direvisi dan dikirim ulang.');
    }

    public function paymentForm(TopUpProposal $proposal): View
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isApproved(), 400, 'Pengajuan belum disetujui.');

        $proposal->load('items.whitelist', 'paymentBatches.items.whitelist');

        return view('topup.payment', compact('proposal'));
    }

    // ─── Simpan Pembayaran VA ──────────────────────────────────

    public function paymentStore(Request $request, TopUpProposal $proposal): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($proposal->user_id === $user->id, 403);
        abort_unless($proposal->isApproved(), 400, 'Pengajuan belum disetujui.');

        $data = $request->validate([
            'batches' => ['required', 'array'],
            'batches.*.batch_id' => ['required', 'exists:top_up_payment_batches,id'],
            'batches.*.va_number' => ['required', 'string', 'max:100'],
        ]);

        $allVaSubmitted = false;

        DB::transaction(function () use ($proposal, $data, &$allVaSubmitted) {
            foreach ($data['batches'] as $batchData) {
                $batch = $proposal->paymentBatches()
                    ->whereKey($batchData['batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless($batch->status === 'waiting_va', 400, 'Batch sudah diproses.');

                $batch->update([
                    'va_number' => $batchData['va_number'],
                    'status' => 'va_submitted',
                ]);

                foreach ($batch->items as $item) {
                    if ($item->payment_status === 'paid') {
                        continue;
                    }

                    $item->update([
                        'va_number' => $batchData['va_number'],
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    $wl = $item->whitelist;
                    abort_if(! $wl, 400, 'Whitelist untuk item ini tidak ditemukan.');
                    $wl->total_topup += (float) $item->nominal;
                    $wl->nominal_terakhir_topup = (float) $item->nominal;
                    $wl->save();
                }
            }

            $pendingCount = $proposal->items()->where('payment_status', 'pending')->count();
            if ($pendingCount === 0) {
                $proposal->update(['status' => 'payment_in_progress']);
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

        $account = $this->topUpAccount();
        $category = $this->topUpCategory();

        DB::transaction(function () use ($proposal, $user, $account, $category) {
            $proposal->update([
                'va_paid_at' => now(),
                'va_paid_by' => $user->id,
            ]);

            foreach ($proposal->paymentBatches()->whereNull('bank_transfer_id')->orderBy('batch_no')->get() as $batch) {
                $bankTransfer = BankTransfer::create([
                    'account_id' => $account->id,
                    'category_id' => $category->id,
                    'type' => 'out',
                    'amount' => $batch->nominal,
                    'description' => 'Top up proposal #'.$proposal->id.' batch #'.$batch->batch_no,
                    'transaction_date' => now(),
                    'created_by' => $user->id,
                    'status' => 'approved',
                    'source_type' => 'top_up_payment_batch',
                    'source_id' => $batch->id,
                ]);

                app(FinanceService::class)->applyBankTransfer($bankTransfer);
                $batch->update([
                    'bank_transfer_id' => $bankTransfer->id,
                    'status' => 'paid',
                ]);
            }
        });

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

        $proposal->load('items.whitelist', 'paymentBatches.items.whitelist');

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

    private function topUpAccount(): Account
    {
        $cfg = config('finance.topup');

        if (!empty($cfg['account_id'])) {
            return Account::whereKey($cfg['account_id'])->where('status', 'active')->firstOrFail();
        }

        if (!empty($cfg['account_name'])) {
            $account = Account::where('name', $cfg['account_name'])->where('status', 'active')->first();
            if ($account) {
                return $account;
            }
        }

        return Account::aktif()->orderBy('id')->firstOrFail();
    }

    private function topUpCategory(): TransactionCategory
    {
        $cfg = config('finance.topup');

        if (!empty($cfg['category_id'])) {
            return TransactionCategory::whereKey($cfg['category_id'])->where('type', 'out')->firstOrFail();
        }

        if (!empty($cfg['category_name'])) {
            $category = TransactionCategory::where('name', $cfg['category_name'])->where('type', 'out')->first();
            if ($category) {
                return $category;
            }
        }

        return TransactionCategory::where('type', 'out')->orderBy('id')->firstOrFail();
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
