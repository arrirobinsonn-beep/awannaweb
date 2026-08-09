<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\RegionalReport;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SpendingHarianController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // ── CS → hanya lihat advertiser yang menjadi atasan langsungnya ─
        if ($user->hasRole('cs')) {
            $advertiserIds = $user->advertiser_id ? [$user->advertiser_id] : [];

            return $this->indexGeneral($request, advertiserIds: $advertiserIds);
        }

        // ── Superadmin / owner / mentor → view folder per advertiser ──
        if ($user->hasRole(['owner', 'super_admin', 'mentor'])) {
            return $this->indexGeneral($request);
        }

        // ── Keuangan → semua data, view advertiser tapi tanpa folder tab ─
        if ($user->hasRole('keuangan')) {
            return $this->indexGeneral($request, allUsers: true);
        }

        // ── Advertiser → hanya miliknya sendiri ───────────────────────
        return $this->indexAdvertiser($request, $user);
    }

    /**
     * Hitung ketidaksesuaian antara RegionalReport vs SpendingHarian untuk user tertentu.
     */
    private function computeDiscrepancy(int $userId, string $dari, string $sampai): array
    {
        // Total regional per tanggal
        $regionalTotals = RegionalReport::where('user_id', $userId)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
            ->groupBy('tgl')
            ->get()
            ->keyBy('tgl');

        // Total spending per tanggal
        $spendingTotals = SpendingHarian::where('user_id', $userId)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
            ->groupBy('tgl')
            ->get()
            ->keyBy('tgl');

        // Semua tanggal unik dari kedua sumber
        $allDates = collect($regionalTotals->keys()->merge($spendingTotals->keys()))
            ->unique()->sort()->values();

        $hasDiscrepancy = false;
        $discrepancies = [];
        $discrepantDates = [];

        foreach ($allDates as $date) {
            $regLead = (int) ($regionalTotals[$date]->total_lead ?? 0);
            $regPaid = (int) ($regionalTotals[$date]->total_paid ?? 0);
            $spLead = (int) ($spendingTotals[$date]->total_lead ?? 0);
            $spPaid = (int) ($spendingTotals[$date]->total_paid ?? 0);

            if ($regLead !== $spLead || $regPaid !== $spPaid) {
                $hasDiscrepancy = true;
                $discrepancies[$date] = [
                    'regional_lead' => $regLead,
                    'regional_paid' => $regPaid,
                    'spending_lead' => $spLead,
                    'spending_paid' => $spPaid,
                ];
                $discrepantDates[$date] = true;
            }
        }

        return compact('hasDiscrepancy', 'discrepancies', 'discrepantDates');
    }

    // ─── View Advertiser: data milik sendiri, group by tanggal → produk ─

    private function indexAdvertiser(Request $request, $user): View
    {
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        $rows = SpendingHarian::with(['product', 'whitelist'])
            ->where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderBy('product_id')
            ->get();

        // Level 1: group by tanggal
        $grouped = $rows->groupBy(fn ($r) => $r->tanggal->format('Y-m-d'));

        $summaries = $grouped->map(function ($items) {
            // Level 2: dalam tiap tanggal, group lagi by produk
            $byProduct = $items->groupBy('product_id')->map(function ($prodItems) {
                return [
                    'product' => $prodItems->first()->product,
                    'spending' => $prodItems->sum('spending'),
                    'lead' => $prodItems->sum('lead'),
                    'paid' => $prodItems->sum('paid'),
                    'paid_ratio' => $prodItems->sum('lead') > 0
                                        ? round($prodItems->sum('paid') / $prodItems->sum('lead') * 100, 0) : 0,
                    'cpa_lead' => $prodItems->sum('lead') > 0
                                        ? round($prodItems->sum('spending') / $prodItems->sum('lead'), 2) : 0,
                    'cpa_paid' => $prodItems->sum('paid') > 0
                                        ? round($prodItems->sum('spending') / $prodItems->sum('paid'), 2) : 0,
                    // Level 3: whitelist-whitelist yang mengiklankan produk ini
                    'whitelists' => $prodItems,
                ];
            });

            return [
                'tanggal' => $items->first()->tanggal,
                'spending' => $items->sum('spending'),
                'lead' => $items->sum('lead'),
                'paid' => $items->sum('paid'),
                'paid_ratio' => $items->sum('lead') > 0
                                    ? round($items->sum('paid') / $items->sum('lead') * 100, 0) : 0,
                'cpa_lead' => $items->sum('lead') > 0
                                    ? round($items->sum('spending') / $items->sum('lead'), 2) : 0,
                'cpa_paid' => $items->sum('paid') > 0
                                    ? round($items->sum('spending') / $items->sum('paid'), 2) : 0,
                'by_product' => $byProduct,          // keyed by product_id
                'total_produk' => $byProduct->count(),
            ];
        });

        // ─── Cek discrepancy: Regional vs Spending ───────────────
        $discrepancy = $this->computeDiscrepancy($user->id, $dari, $sampai);
        $hasDiscrepancy = $discrepancy['hasDiscrepancy'];
        $discrepancies = $discrepancy['discrepancies'];
        $discrepantDates = $discrepancy['discrepantDates'];

        // ─── Cek discrepancy: Data CS tim vs data advertiser ────
        $csTeamIds = User::where('advertiser_id', $user->id)
            ->where('is_active', true)->pluck('id');

        $csDiscrepancy = ['has_discrepancy' => false, 'dates' => []];
        if ($csTeamIds->isNotEmpty()) {
            // Ambil total spending per tanggal untuk CS team
            $csTotals = SpendingHarian::whereIn('user_id', $csTeamIds)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->selectRaw('DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
                ->groupBy('tgl')
                ->get()
                ->keyBy('tgl');

            // Bandingkan dengan data advertiser per tanggal
            $advTotals = SpendingHarian::where('user_id', $user->id)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->selectRaw('DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
                ->groupBy('tgl')
                ->get()
                ->keyBy('tgl');

            $allTgl = collect($csTotals->keys()->merge($advTotals->keys()))->unique()->sort()->values();

            foreach ($allTgl as $tgl) {
                $csLead = (int) ($csTotals[$tgl]->total_lead ?? 0);
                $csPaid = (int) ($csTotals[$tgl]->total_paid ?? 0);
                $advLead = (int) ($advTotals[$tgl]->total_lead ?? 0);
                $advPaid = (int) ($advTotals[$tgl]->total_paid ?? 0);

                // Flag jika CS punya data tapi berbeda, atau CS punya data dan advertiser belum punya
                $hasCsData = $csLead > 0 || $csPaid > 0;
                if ($hasCsData && ($csLead !== $advLead || $csPaid !== $advPaid)) {
                    $csDiscrepancy['has_discrepancy'] = true;
                    $csDiscrepancy['dates'][$tgl] = [
                        'cs_lead' => $csLead,
                        'cs_paid' => $csPaid,
                        'adv_lead' => $advLead,
                        'adv_paid' => $advPaid,
                    ];
                }
            }
        }

        // Whitelist milik advertiser ini (untuk form filter/info)
        $myWhitelists = Whitelist::where('user_id', $user->id)->aktif()->get();

        // Guard tombol "+ Input Spending": user wajib punya minimal 1 whitelist
        $hasWhitelist = Whitelist::where('user_id', $user->id)->exists();

        // ─── Quick-change tanggal: tanggal yang sudah punya data (whitelist+produk sama)
        //     harus dinonaktifkan di date picker agar tidak terjadi double data. ──
        $recentRecords = SpendingHarian::where('user_id', $user->id)
            ->whereBetween('tanggal', [now()->subDays(180)->format('Y-m-d'), now()->format('Y-m-d')])
            ->get(['tanggal', 'whitelist_id', 'product_id']);

        // Map combo (whitelist|produk) → tanggal-tanggal yang sudah memakainya
        $comboDates = [];
        foreach ($recentRecords as $rec) {
            $combo = $rec->whitelist_id.'|'.$rec->product_id;
            $comboDates[$combo][$rec->tanggal->format('Y-m-d')] = true;
        }

        $dateChangeRestrictions = [];
        foreach ($summaries as $dateKey => $s) {
            $combos = [];
            foreach ($s['by_product'] as $prodData) {
                foreach ($prodData['whitelists'] as $item) {
                    $combos[$item->whitelist_id.'|'.$item->product_id] = true;
                }
            }

            $disabled = [];
            foreach (array_keys($combos) as $combo) {
                foreach (array_keys($comboDates[$combo] ?? []) as $d) {
                    if ($d !== $dateKey) {
                        $disabled[$d] = true;
                    }
                }
            }
            $dateChangeRestrictions[$dateKey] = array_keys($disabled);
        }

        return view('spending.index-advertiser', compact(
            'summaries', 'dari', 'sampai', 'myWhitelists', 'user',
            'hasDiscrepancy', 'discrepancies', 'discrepantDates',
            'csDiscrepancy', 'hasWhitelist', 'dateChangeRestrictions'
        ));
    }

    // ─── View General: folder tab per advertiser ───────────────────

    private function indexGeneral(Request $request, bool $allUsers = false, array $advertiserIds = []): View
    {
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        // Semua advertiser — termasuk yang belum punya data spending
        $advertisers = User::role('advertiser')
            ->when($advertiserIds, fn ($q) => $q->whereIn('id', $advertiserIds))
            ->orderBy('nama')
            ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

        if ($advertisers->isEmpty()) {
            return view('spending.index-general', [
                'dataPerAdvertiser' => [],
                'advertisers' => $advertisers,
                'activeTab' => null,
                'dari' => $dari,
                'sampai' => $sampai,
            ]);
        }

        // ─── BATCH: Ambil semua spending untuk semua advertiser dalam 1 query ──
        $allSpending = SpendingHarian::with(['product', 'whitelist'])
            ->whereIn('user_id', $advertisers->pluck('id'))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderBy('product_id')
            ->get()
            ->groupBy('user_id');

        // ─── BATCH: Ambil semua regional & spending totals untuk discrepancy ──
        $advIds = $advertisers->pluck('id');

        $regionalTotals = RegionalReport::whereIn('user_id', $advIds)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('user_id, DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
            ->groupBy('user_id', 'tgl')
            ->get()
            ->groupBy('user_id');

        $spendingTotals = SpendingHarian::whereIn('user_id', $advIds)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('user_id, DATE(tanggal) as tgl, COALESCE(SUM(`lead`),0) as total_lead, COALESCE(SUM(paid),0) as total_paid')
            ->groupBy('user_id', 'tgl')
            ->get()
            ->groupBy('user_id');

        // ─── BATCH: Proses semua advertiser ──
        $dataPerAdvertiser = [];
        foreach ($advertisers as $adv) {
            $rows = $allSpending->get($adv->id, collect());

            // Hitung discrepancy dari batch data
            $disc = $this->computeDiscrepancyBatch(
                $adv->id, $dari, $sampai,
                $regionalTotals->get($adv->id, collect()),
                $spendingTotals->get($adv->id, collect())
            );

            if ($rows->isEmpty()) {
                $dataPerAdvertiser[$adv->id] = [
                    'user' => $adv,
                    'summaries' => collect(),
                    'total_spending' => 0,
                    'has_discrepancy' => $disc['hasDiscrepancy'],
                    'discrepancies' => $disc['discrepancies'],
                    'discrepant_dates' => $disc['discrepantDates'],
                ];

                continue;
            }

            $grouped = $rows->groupBy(fn ($r) => $r->tanggal->format('Y-m-d'));

            $dataPerAdvertiser[$adv->id] = [
                'user' => $adv,
                'total_spending' => $rows->sum('spending'),
                'has_discrepancy' => $disc['hasDiscrepancy'],
                'discrepancies' => $disc['discrepancies'],
                'discrepant_dates' => $disc['discrepantDates'],
                'summaries' => $grouped->map(function ($items) {
                    $byProduct = $items->groupBy('product_id')->map(function ($pItems) {
                        return [
                            'product' => $pItems->first()->product,
                            'spending' => $pItems->sum('spending'),
                            'lead' => $pItems->sum('lead'),
                            'paid' => $pItems->sum('paid'),
                            'paid_ratio' => $pItems->sum('lead') > 0
                                                ? round($pItems->sum('paid') / $pItems->sum('lead') * 100, 2) : 0,
                            'cpa_lead' => $pItems->sum('lead') > 0
                                                ? round($pItems->sum('spending') / $pItems->sum('lead'), 2) : 0,
                            'cpa_paid' => $pItems->sum('paid') > 0
                                                ? round($pItems->sum('spending') / $pItems->sum('paid'), 2) : 0,
                            'whitelists' => $pItems,
                        ];
                    });

                    return [
                        'tanggal' => $items->first()->tanggal,
                        'spending' => $items->sum('spending'),
                        'lead' => $items->sum('lead'),
                        'paid' => $items->sum('paid'),
                        'paid_ratio' => $items->sum('lead') > 0
                                              ? round($items->sum('paid') / $items->sum('lead') * 100, 2) : 0,
                        'cpa_lead' => $items->sum('lead') > 0
                                              ? round($items->sum('spending') / $items->sum('lead'), 2) : 0,
                        'cpa_paid' => $items->sum('paid') > 0
                                              ? round($items->sum('spending') / $items->sum('paid'), 2) : 0,
                        'by_product' => $byProduct,
                        'total_produk' => $byProduct->count(),
                    ];
                }),
            ];
        }

        // Tab aktif (dari query string atau advertiser pertama)
        $activeTab = $request->input('tab', $advertisers->first()?->id);

        return view('spending.index-general', compact(
            'dataPerAdvertiser', 'advertisers', 'activeTab', 'dari', 'sampai'
        ));
    }

    /**
     * Batch version: hitung discrepancy dari data yang sudah di-batch.
     */
    private function computeDiscrepancyBatch(int $userId, string $dari, string $sampai, $regionalTotals, $spendingTotals): array
    {
        $regionalKeyed = $regionalTotals->keyBy('tgl');
        $spendingKeyed = $spendingTotals->keyBy('tgl');

        // Semua tanggal unik dari kedua sumber
        $allDates = collect($regionalKeyed->keys()->merge($spendingKeyed->keys()))
            ->unique()->sort()->values();

        $hasDiscrepancy = false;
        $discrepancies = [];
        $discrepantDates = [];

        foreach ($allDates as $date) {
            $regLead = (int) ($regionalKeyed[$date]->total_lead ?? 0);
            $regPaid = (int) ($regionalKeyed[$date]->total_paid ?? 0);
            $spLead = (int) ($spendingKeyed[$date]->total_lead ?? 0);
            $spPaid = (int) ($spendingKeyed[$date]->total_paid ?? 0);

            if ($regLead !== $spLead || $regPaid !== $spPaid) {
                $hasDiscrepancy = true;
                $discrepancies[$date] = [
                    'regional_lead' => $regLead,
                    'regional_paid' => $regPaid,
                    'spending_lead' => $spLead,
                    'spending_paid' => $spPaid,
                ];
                $discrepantDates[$date] = true;
            }
        }

        return compact('hasDiscrepancy', 'discrepancies', 'discrepantDates');
    }

    // ─── Create ────────────────────────────────────────────────────

    public function create(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        // Guard: advertiser belum boleh input spending sebelum punya whitelist
        if ($user->hasRole('advertiser') && ! Whitelist::where('user_id', $user->id)->exists()) {
            return redirect()->route('whitelist.index')
                ->with('error', 'Ups, kamu belum memiliki satupun akun WL. Silakan buat akun Whitelist terlebih dahulu.');
        }

        $whitelists = Whitelist::aktif();

        if ($user->hasRole('advertiser')) {
            $whitelists = $whitelists->where('user_id', $user->id);
        } elseif ($user->hasRole('cs') && $user->advertiser_id) {
            $whitelists = $whitelists->where('user_id', $user->advertiser_id);
        }

        $whitelists = $whitelists->get(['id', 'nama', 'kode', 'platform']);

        $products = Product::aktif()->get(['id', 'name', 'code']);

        // Dukung deep-link ?tanggal= dari halaman index (tombol "＋" per tanggal)
        $tanggal = $request->query('tanggal', now()->format('Y-m-d'));

        return view('spending.form', [
            'spending' => new SpendingHarian,
            'whitelists' => $whitelists,
            'products' => $products,
            'tanggal' => $tanggal,
            'mode' => 'create',
        ]);
    }

    // ─── Store ─────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.whitelist_id' => ['required', 'exists:whitelists,id'],
            'items.*.spending' => ['required', 'numeric', 'min:0'],
            'items.*.lead' => ['required', 'integer', 'min:0'],
            'items.*.paid' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();
        $tanggal = $validated['tanggal'];
        $items = $validated['items'];

        // Validasi: advertiser hanya boleh pakai whitelist miliknya
        if ($user->hasRole('advertiser')) {
            $wlIds = array_unique(array_column($items, 'whitelist_id'));
            $whitelists = Whitelist::whereIn('id', $wlIds)->get()->keyBy('id');
            foreach ($wlIds as $wlId) {
                $wl = $whitelists->get($wlId);
                abort_unless($wl && $wl->user_id === $user->id, 403, 'Whitelist bukan milik Anda.');
            }
        }

        $imported = 0;
        $affectedWhitelistIds = [];

        DB::transaction(function () use ($items, $user, $tanggal, &$imported, &$affectedWhitelistIds) {
            foreach ($items as $item) {
                $data = [
                    'tanggal' => $tanggal,
                    'user_id' => $user->id,
                    'whitelist_id' => $item['whitelist_id'],
                    'product_id' => $item['product_id'],
                    'spending' => $item['spending'],
                    'lead' => $item['lead'],
                    'paid' => $item['paid'],
                ];

                SpendingHarian::computeMetrics($data);
                SpendingHarian::create($data);
                $imported++;

                $affectedWhitelistIds[] = $item['whitelist_id'];
            }
        });

        // Update total_spending untuk setiap whitelist yang terkena dampak
        collect($affectedWhitelistIds)->unique()->each(function ($wlId) {
            Whitelist::find($wlId)?->recalculateTotalSpending();
        });

        // ─── Notifikasi ke advertiser jika CS yang input ───────
        if ($user->hasRole('cs')) {
            $notified = [];
            collect($affectedWhitelistIds)->unique()->each(function ($wlId) use ($user, $tanggal, &$notified) {
                $wl = Whitelist::find($wlId);
                if ($wl && $wl->user_id !== $user->id && ! in_array($wl->user_id, $notified)) {
                    $notified[] = $wl->user_id;
                    Notification::create([
                        'user_id' => $wl->user_id,
                        'from_user_id' => $user->id,
                        'type' => 'spending_correction',
                        'title' => 'Koreksi Data Spending oleh CS',
                        'message' => "CS {$user->panggilan} telah menginput data spending tanggal {$tanggal}. Silakan sesuaikan data Anda.",
                        'data' => ['url' => route('spending.index')],
                    ]);
                }
            });
        }

        return redirect()->route('spending.index')
            ->with('success', "Berhasil menyimpan {$imported} data spending.");
    }

    // ─── Ubah Tanggal (quick-change dari halaman advertiser) ───────

    public function changeDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sumber' => ['required', 'date'],
            // Tanggal masa depan tidak bisa dipilih (sama seperti date picker)
            'target' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $user = Auth::user();
        $sumber = $validated['sumber'];
        $target = $validated['target'];

        // Memilih tanggal yang sama = tidak ada perubahan (no-op)
        if ($target === $sumber) {
            return response()->json(['success' => true, 'moved' => 0, 'message' => 'Tidak ada perubahan tanggal.'], 200);
        }

        $records = SpendingHarian::where('user_id', $user->id)
            ->whereDate('tanggal', $sumber)
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tanggal sumber tidak ditemukan.',
            ], 404);
        }

        $ids = $records->pluck('id');

        // Cegah double data: target tidak boleh sudah punya whitelist+produk yang sama.
        // Record yang sedang dipindah (id dalam $ids) dikecualikan agar tidak false-positive.
        foreach ($records as $r) {
            $exists = SpendingHarian::where('user_id', $user->id)
                ->whereDate('tanggal', $target)
                ->where('whitelist_id', $r->whitelist_id)
                ->where('product_id', $r->product_id)
                ->whereNotIn('id', $ids)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => "Tanggal {$target} sudah memiliki data untuk whitelist/produk yang sama. Pilih tanggal lain.",
                ], 422);
            }
        }

        SpendingHarian::whereIn('id', $ids)->update(['tanggal' => $target]);

        return response()->json([
            'success' => true,
            'moved' => $records->count(),
            'message' => "Tanggal berhasil diubah ke {$target}.",
        ]);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function show(SpendingHarian $spending): View
    {
        $this->authorizeAccess($spending);
        $spending->load('user', 'whitelist', 'product');

        return view('spending.show', compact('spending'));
    }

    // ─── Edit ──────────────────────────────────────────────────────

    public function edit(SpendingHarian $spending): View
    {
        $this->authorizeAccess($spending);

        $user = Auth::user();
        $whitelists = Whitelist::aktif();

        if ($user->hasRole('advertiser')) {
            $whitelists = $whitelists->where('user_id', $user->id);
        }

        $whitelists = $whitelists->get(['id', 'nama', 'kode', 'platform']);

        $products = Product::aktif()->get(['id', 'name', 'code']);

        return view('spending.form', [
            'spending' => $spending,
            'whitelists' => $whitelists,
            'products' => $products,
            'mode' => 'edit',
        ]);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function update(Request $request, SpendingHarian $spending): JsonResponse|RedirectResponse
    {
        $this->authorizeAccess($spending);

        $oldWhitelistId = $spending->whitelist_id;

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'whitelist_id' => ['required', 'exists:whitelists,id'],
            'product_id' => ['required', 'exists:products,id'],
            'spending' => ['required', 'numeric', 'min:0'],
            'lead' => ['required', 'integer', 'min:0'],
            'paid' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        SpendingHarian::computeMetrics($data);
        $spending->update($data);

        // Rekalkulasi whitelist lama (jika whitelist_id berubah)
        if ((int) $oldWhitelistId !== (int) $data['whitelist_id']) {
            Whitelist::find($oldWhitelistId)?->recalculateTotalSpending();
        }

        // Rekalkulasi whitelist baru
        $spending->fresh()->whitelist->recalculateTotalSpending();

        // ─── Notifikasi ke advertiser jika CS yang edit ───────
        $this->notifyWhitelistOwner($spending);

        // AJAX (modal edit di halaman advertiser) → balas JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data spending berhasil diperbarui.',
                'data' => [
                    'id' => $spending->id,
                    'spending' => (float) $data['spending'],
                    'lead' => (int) $data['lead'],
                    'paid' => (int) $data['paid'],
                    'paid_ratio' => $data['paid_ratio'],
                    'cpa_lead' => $data['cpa_lead'],
                    'cpa_paid' => $data['cpa_paid'],
                ],
            ]);
        }

        return redirect()->route('spending.index')
            ->with('success', 'Data spending berhasil diperbarui.');
    }

    // ─── Destroy ───────────────────────────────────────────────────

    public function destroy(SpendingHarian $spending): RedirectResponse
    {
        $this->authorizeAccess($spending);

        // Simpan whitelist sebelum spending dihapus
        $whitelist = $spending->whitelist;

        // ─── Notifikasi ke advertiser jika CS yang hapus ───────
        $this->notifyWhitelistOwner($spending);

        $spending->delete();

        // Update total_spending di whitelist
        $whitelist->recalculateTotalSpending();

        return redirect()->route('spending.index')
            ->with('success', 'Data spending berhasil dihapus.');
    }

    // ─── Bulk Destroy (hapus massal via centang di detail spending) ─

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $user = Auth::user();
        $ids = array_map('intval', $validated['ids']);

        // Advertiser hanya boleh menghapus data miliknya sendiri
        $query = SpendingHarian::with('whitelist')->whereIn('id', $ids);
        if ($user->hasRole('advertiser')) {
            $query->where('user_id', $user->id);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang valid untuk dihapus.');
        }

        // ─── Notifikasi ke pemilik whitelist bila yang menghapus adalah CS ──
        foreach ($rows as $spending) {
            $this->notifyWhitelistOwner($spending);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $spending) {
                $spending->delete();
            }
        });

        // ─── Rekalkulasi total_spending whitelist yang terdampak (batch) ──
        $wlIds = $rows->pluck('whitelist_id')->unique()->values();
        if ($wlIds->isNotEmpty()) {
            $totals = SpendingHarian::whereIn('whitelist_id', $wlIds)
                ->selectRaw('whitelist_id, COALESCE(SUM(spending),0) as total')
                ->groupBy('whitelist_id')
                ->pluck('total', 'whitelist_id');

            Whitelist::whereIn('id', $wlIds)->get()->each(function ($wl) use ($totals) {
                $wl->update(['total_spending' => (float) ($totals[$wl->id] ?? 0)]);
            });
        }

        return back()->with('success', $rows->count().' data spending berhasil dihapus.');
    }

    // ─── Approve ───────────────────────────────────────────────────

    public function approve(SpendingHarian $spending): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole(['owner', 'super_admin', 'keuangan', 'admin']), 403);
        $spending->update(['status' => 'approved']);

        return back()->with('success', 'Data spending disetujui.');
    }

    // ─── Private helper ────────────────────────────────────────────

    /**
     * Advertiser hanya bisa akses data miliknya.
     * Role lain (cs, superadmin, mentor, keuangan, admin, owner) bisa akses semua.
     */
    private function authorizeAccess(SpendingHarian $spending): void
    {
        $user = Auth::user();
        if ($user->hasRole('advertiser') && $spending->user_id !== $user->id) {
            // AJAX (modal edit) → balas JSON; request biasa → 403 HTML
            abort(request()->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data ini.'], 403)
                : 403, 'Anda tidak memiliki akses ke data ini.');
        }
    }

    /**
     * Helper: kirim notifikasi ke whitelist owner saat CS mengubah data spending.
     */
    private function notifyWhitelistOwner(SpendingHarian $spending): void
    {
        $user = Auth::user();
        if (! $user->hasRole('cs')) {
            return;
        }

        $wl = $spending->whitelist;
        if ($wl && $wl->user_id !== $user->id) {
            Notification::create([
                'user_id' => $wl->user_id,
                'from_user_id' => $user->id,
                'type' => 'spending_correction',
                'title' => 'Koreksi Data Spending oleh CS',
                'message' => "CS {$user->panggilan} telah mengubah data spending tanggal {$spending->tanggal->format('Y-m-d')}. Silakan sesuaikan data Anda.",
                'data' => ['url' => route('spending.index')],
            ]);
        }
    }
}
