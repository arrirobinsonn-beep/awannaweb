<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\RegionalReport;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use App\Services\ProductNameMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        // ─── Ringkasan periode: 4 kartu di atas tabel (mengikuti $dari/$sampai) ──
        $summary = [
            'spending' => (float) $rows->sum('spending'),
            'lead' => (int) $rows->sum('lead'),
            'paid' => (int) $rows->sum('paid'),
        ];
        $summary['paid_ratio'] = $summary['lead'] > 0
            ? round($summary['paid'] / $summary['lead'] * 100, 0) : 0;
        $summary['cpa_lead'] = $summary['lead'] > 0
            ? round($summary['spending'] / $summary['lead'], 0) : 0;
        $summary['cpa_paid'] = $summary['paid'] > 0
            ? round($summary['spending'] / $summary['paid'], 0) : 0;

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
            'summaries', 'summary', 'dari', 'sampai', 'myWhitelists', 'user',
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
            // Multi-tanggal: setiap item bisa membawa tanggal sendiri (fitur upload Excel).
            // Global 'tanggal' tetap didukung untuk form lama / edit.
            'tanggal' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.tanggal' => ['required_without:tanggal', 'nullable', 'date'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.whitelist_id' => ['required', 'exists:whitelists,id'],
            'items.*.spending' => ['required', 'numeric', 'min:0'],
            'items.*.lead' => ['required', 'integer', 'min:0'],
            'items.*.paid' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();
        $globalTanggal = $validated['tanggal'] ?? null;
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
        $skipped = 0;
        $affectedWhitelists = []; // whitelist_id => tanggal (untuk notifikasi CS)

        // ─── Batch: combo (tanggal|whitelist|produk) yang SUDAH ada, agar import ulang
        //     file yang sama tidak menggandakan data (1 baris per combo per tanggal). ──
        $tanggalList = array_values(array_unique(array_map(fn ($i) => $i['tanggal'] ?? $globalTanggal, $items)));
        $existingCombos = SpendingHarian::where('user_id', $user->id)
            ->whereIn('tanggal', $tanggalList)
            ->whereIn('whitelist_id', array_values(array_unique(array_column($items, 'whitelist_id'))))
            ->whereIn('product_id', array_values(array_unique(array_column($items, 'product_id'))))
            ->get(['tanggal', 'whitelist_id', 'product_id'])
            ->map(fn ($r) => $r->tanggal->format('Y-m-d').'|'.$r->whitelist_id.'|'.$r->product_id)
            ->flip();

        $seenCombos = [];

        DB::transaction(function () use ($items, $user, $globalTanggal, &$imported, &$skipped, &$affectedWhitelists, $existingCombos, &$seenCombos) {
            foreach ($items as $item) {
                $tanggal = $item['tanggal'] ?? $globalTanggal;
                abort_unless($tanggal, 422, 'Tanggal tidak lengkap pada salah satu data.');

                $combo = $tanggal.'|'.$item['whitelist_id'].'|'.$item['product_id'];
                if (isset($existingCombos[$combo]) || isset($seenCombos[$combo])) {
                    $skipped++;
                    continue;
                }
                $seenCombos[$combo] = true;

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

                $affectedWhitelists[$item['whitelist_id']] = $tanggal;
            }
        });

        // ─── Update total_spending whitelist terdampak secara BATCH (2 query) ──
        $this->recalculateWhitelistTotals(array_keys($affectedWhitelists));

        // ─── Notifikasi ke advertiser jika CS yang input ───────
        if ($user->hasRole('cs')) {
            $notified = [];
            foreach ($affectedWhitelists as $wlId => $tanggal) {
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
            }
        }

        $message = "Berhasil menyimpan {$imported} data spending.";
        if ($skipped > 0) {
            $message .= " {$skipped} data dilewati karena sudah tercatat (tanggal + whitelist + produk yang sama).";
        }

        return redirect()->route('spending.index')
            ->with('success', $message);
    }

    // ─── Upload Excel Meta Ads Manager ──────────────────────────────

    /** Map nama bulan (Indonesia & Inggris) → angka bulan */
    private const BULAN = [
        'jan' => '01', 'januari' => '01',
        'feb' => '02', 'februari' => '02',
        'mar' => '03', 'maret' => '03',
        'apr' => '04', 'april' => '04',
        'mei' => '05', 'may' => '05',
        'jun' => '06', 'juni' => '06',
        'jul' => '07', 'juli' => '07',
        'agu' => '08', 'aug' => '08', 'agustus' => '08',
        'sep' => '09', 'september' => '09',
        'okt' => '10', 'oct' => '10', 'oktober' => '10',
        'nov' => '11', 'november' => '11',
        'des' => '12', 'dec' => '12', 'desember' => '12',
    ];

    /**
     * Terima 1..N file Excel dari Meta Ads Manager.
     * Nama file memuat kode whitelist + rentang tanggal; isi file memuat
     * nama kampanye (diawali kode produk) & kolom "Jumlah yang dibelanjakan (IDR)".
     * Hasil dikelompokkan per tanggal agar bisa di-apply ke form multi-tanggal.
     */
    public function parseUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            // File regional (lead/paid) wajib menyertai file Ads Manager
            'regional' => ['required', 'array', 'min:1', 'max:20'],
            'regional.*' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $user = Auth::user();

        // Whitelist yang boleh dipakai: milik advertiser ini (atau atasan bila CS)
        $advertiserId = $user->hasRole('advertiser') ? $user->id : ($user->advertiser_id ?? $user->id);

        $whitelists = Whitelist::where('user_id', $advertiserId)
            ->get(['id', 'nama', 'kode', 'platform'])
            ->keyBy('kode');

        // Produk aktif, urutkan kode terpanjang dulu agar prefix-match tepat (KSP2 sebelum KSP)
        // Skema baru: kolom products bernama `code`/`name` (bukan kode_produk/nama_produk)
        $products = Product::aktif()->get(['id', 'code', 'name'])
            ->sortByDesc(fn ($p) => strlen($p->code))
            ->values();

        // ── 1) Parse file Ads Manager → spending per (tanggal, whitelist, produk) ──
        $adsResults = [];
        $spendingMap = []; // key: tanggal|wlId|prodId => total spending
        foreach ($request->file('files') as $file) {
            $r = $this->parseMetaFile($file, $whitelists, $products);
            $adsResults[] = $r;
            if ($r['ok']) {
                foreach ($r['groups'] as $g) {
                    foreach ($g['products'] as $p) {
                        $key = $g['tanggal'].'|'.$r['whitelist']['id'].'|'.$p['product_id'];
                        $spendingMap[$key] = ($spendingMap[$key] ?? 0) + $p['spending'];
                    }
                }
            }
        }

        // ── 2) Parse file regional → lead & paid per (tanggal, whitelist, produk) ──
        $matcher = new ProductNameMatcher();
        $productIndex = $matcher->buildIndex();
        $regionalResults = [];
        $leadPaidMap = []; // key: tanggal|wlId|prodId => [lead, paid]
        $regionalUnmatched = [];
        foreach ($request->file('regional') as $file) {
            $r = $this->parseRegionalFile($file, $whitelists, $matcher, $productIndex);
            $regionalResults[] = $r;
            foreach ($r['map'] as $key => $lp) {
                $leadPaidMap[$key]['lead'] = ($leadPaidMap[$key]['lead'] ?? 0) + ($lp['lead'] ?? 0);
                $leadPaidMap[$key]['paid'] = ($leadPaidMap[$key]['paid'] ?? 0) + ($lp['paid'] ?? 0);
            }
            foreach ($r['unmatched'] as $u) {
                $regionalUnmatched[] = $u;
            }
        }

        // ── 3) Gabungkan: spending dari ads + lead/paid dari regional ──
        $allKeys = array_unique(array_merge(array_keys($spendingMap), array_keys($leadPaidMap)));
        $combined = []; // tanggal => [ wlId => [ 'whitelist'=>..., 'products'=>[pid=>...] ] ]

        foreach ($allKeys as $key) {
            [$tgl, $wlId, $prodId] = explode('|', $key);
            $wl = $whitelists->firstWhere('id', (int) $wlId);
            $prod = $products->firstWhere('id', (int) $prodId);
            if (! $wl || ! $prod) {
                continue;
            }

            $combined[$tgl][$wlId]['whitelist'] = [
                'id' => $wl->id,
                'nama' => $wl->nama,
                'kode' => $wl->kode,
                'platform' => $wl->platform,
            ];
            $combined[$tgl][$wlId]['products'][$prodId] = [
                'product_id' => $prod->id,
                'product_name' => $prod->name.' ('.$prod->code.')',
                'spending' => round($spendingMap[$key] ?? 0, 2),
                'lead' => $leadPaidMap[$key]['lead'] ?? 0,
                'paid' => $leadPaidMap[$key]['paid'] ?? 0,
            ];
        }

        // Sort tanggal menaik, produk per tanggal, whitelist per tanggal
        ksort($combined);
        $grouped = [];
        foreach ($combined as $tgl => $byWl) {
            ksort($byWl);
            $wlList = [];
            foreach ($byWl as $wlId => $wlData) {
                $products = array_values($wlData['products']);
                usort($products, fn ($a, $b) => strcmp($a['product_name'], $b['product_name']));
                $wlList[] = [
                    'whitelist' => $wlData['whitelist'],
                    'products' => $products,
                    'total_spending' => round(array_sum(array_column($products, 'spending')), 2),
                    'total_lead' => array_sum(array_column($products, 'lead')),
                    'total_paid' => array_sum(array_column($products, 'paid')),
                ];
            }
            $grouped[] = ['tanggal' => $tgl, 'whitelists' => $wlList];
        }

        return response()->json([
            'success' => true,
            'combined' => $grouped,
            'ads_files' => count($adsResults),
            'regional_files' => count($regionalResults),
            'regional_unmatched' => array_slice(array_values(array_unique($regionalUnmatched)), 0, 20),
            'regional_unmatched_count' => count(array_unique($regionalUnmatched)),
            'total_rows' => count($allKeys),
        ]);
    }

    /**
     * Parse file regional (export order online / detail per daerah):
     * kolom "product" (format "P.1 - Nama Produk - 22760"), "payment_status", dan "created_at".
     * Nama produk dipecah 3 area dengan separator "-":
     *   area 1 = kode teritorial advertiser (diabaikan),
     *   area 2 = nama produk → dicocokkan fuzzy ke products.name,
     *   area 3 = kode whitelist → dicocokkan ke whitelists.kode.
     * Setiap baris = 1 lead; baris dengan payment_status "paid" menambah paid.
     */
    private function parseRegionalFile($file, $whitelists, ProductNameMatcher $matcher, array $productIndex): array
    {
        $name = $file->getClientOriginalName();

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $rows = $reader->load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return [
                'file' => $name,
                'ok' => false,
                'error' => 'Gagal membaca file regional: '.$e->getMessage(),
                'map' => [],
                'unmatched' => [],
            ];
        }

        if (empty($rows)) {
            return ['file' => $name, 'ok' => false, 'error' => 'File regional kosong.', 'map' => [], 'unmatched' => []];
        }

        // ── Deteksi baris header: cari kolom product / payment_status / created_at ──
        // Prioritas nama: PERSIS dulu (mis. "payment_status"), lalu mengandung,
        // lalu fallback "status" umum — agar tidak salah ambil kolom status lain
        // (mis. "status" order) yang nilainya bukan paid/unpaid.
        $colProduct = $colStatus = $colDate = null;
        $headerRow = -1;

        foreach ($rows as $i => $row) {
            // Reset per baris agar baris judul/data tidak mencemari hasil
            $cProduct = $cStatus = $cDate = null;
            $normalized = array_map(fn ($v) => mb_strtolower(trim((string) ($v ?? ''))), $row);

            // 1) Nama persis
            foreach ($normalized as $ci => $lv) {
                if ($lv === '') {
                    continue;
                }
                if ($cProduct === null && ($lv === 'product' || $lv === 'produk')) {
                    $cProduct = $ci;
                }
                if ($cStatus === null && in_array($lv, ['payment_status', 'payment status', 'status_pembayaran', 'status pembayaran'], true)) {
                    $cStatus = $ci;
                }
                if ($cDate === null && ($lv === 'created_at' || $lv === 'tanggal')) {
                    $cDate = $ci;
                }
            }

            // 2) Mengandung kata kunci
            foreach ($normalized as $ci => $lv) {
                if ($cProduct === null && (str_contains($lv, 'product') || str_contains($lv, 'produk'))) {
                    $cProduct = $ci;
                }
                if ($cStatus === null && (str_contains($lv, 'payment_status') || str_contains($lv, 'payment status') || str_contains($lv, 'status_pembayaran') || str_contains($lv, 'status pembayaran'))) {
                    $cStatus = $ci;
                }
                if ($cDate === null && (str_contains($lv, 'created_at') || str_contains($lv, 'tanggal'))) {
                    $cDate = $ci;
                }
            }

            // 3) Fallback terakhir: kolom berisi "status" (bila payment_status tak ada)
            if ($cStatus === null) {
                foreach ($normalized as $ci => $lv) {
                    if (str_contains($lv, 'status')) {
                        $cStatus = $ci;
                        break;
                    }
                }
            }

            if ($cProduct !== null && $cStatus !== null) {
                $colProduct = $cProduct;
                $colStatus = $cStatus;
                $colDate = $cDate;
                $headerRow = $i;
                break;
            }
        }

        if ($headerRow < 0) {
            return [
                'file' => $name,
                'ok' => false,
                'error' => 'Kolom "product" dan "payment_status" tidak ditemukan di file regional. Kolom tersedia: '.implode(', ', array_slice(array_map(fn ($v) => trim((string) $v), $rows[0] ?? []), 0, 12)),
                'map' => [],
                'unmatched' => [],
            ];
        }

        $map = [];
        $unmatched = [];

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rawProduct = trim((string) ($row[$colProduct] ?? ''));
            if ($rawProduct === '') {
                continue;
            }

            $status = mb_strtolower(trim((string) ($row[$colStatus] ?? '')));
            // Normalisasi nilai: buang spasi/simbol/akhiran (mis. "PAID", "Paid",
            // "paid by buyer", "Paid - lunas") lalu cek diawali "paid" (bukan "unpaid").
            $statusNorm = preg_replace('/[^a-z0-9]/', '', $status);
            $isPaid = $statusNorm !== '' && str_starts_with($statusNorm, 'paid');

            $tanggal = null;
            if ($colDate !== null) {
                $rawDate = $row[$colDate] ?? null;
                // Buang bagian waktu bila ada (mis. "01-07-2026 - 23:18" atau "2026-07-01 12:30")
                if (is_string($rawDate)) {
                    $rawDate = preg_replace('/[\s\-]+\d{1,2}[:.]\d{2}.*$/', '', $rawDate);
                }
                $tanggal = $this->parseCellDate($rawDate);
            }
            if (! $tanggal) {
                $tanggal = now()->format('Y-m-d');
            }

            // ── Pecah nama produk jadi 3 area: teritorial / nama / kode whitelist ──
            $parts = preg_split('/\s*-\s*/', $rawProduct);
            $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

            if (count($parts) < 2) {
                $unmatched[] = "Baris ".($i + 1).": '{$rawProduct}' (format nama produk tidak lengkap)";
                continue;
            }

            $wlCode = trim(end($parts)); // area ke-3 = kode whitelist
            $wl = $whitelists[$wlCode] ?? null;
            if (! $wl) {
                $unmatched[] = "Baris ".($i + 1).": '{$rawProduct}' (kode whitelist {$wlCode} tidak dikenal)";
                continue;
            }

            // Area ke-2 = nama produk (buang area 1 teritorial & area 3 kode whitelist)
            $nameParts = array_slice($parts, 1, -1);
            $productName = trim(implode(' - ', $nameParts));
            if ($productName === '') {
                $productName = trim($parts[0]);
            }

            $prod = $matcher->match($productName, $productIndex);
            if (! $prod) {
                $unmatched[] = "Baris ".($i + 1).": '{$rawProduct}' (produk '{$productName}' tidak dikenal)";
                continue;
            }

            $key = $tanggal.'|'.$wl->id.'|'.$prod->id;
            $map[$key]['lead'] = ($map[$key]['lead'] ?? 0) + 1;
            // Selalu set 'paid' (0 bila semua baris unpaid) agar key konsisten
            $map[$key]['paid'] = ($map[$key]['paid'] ?? 0) + ($isPaid ? 1 : 0);
        }

        return [
            'file' => $name,
            'ok' => true,
            'map' => $map,
            'unmatched' => $unmatched,
        ];
    }

    private function parseMetaFile($file, $whitelists, $products): array
    {
        $name = $file->getClientOriginalName();
        $base = pathinfo($name, PATHINFO_FILENAME);

        // 1) Rentang tanggal dari nama file (mis. 5-Agu-2026 s/d 5-Agu-2026)
        $dates = $this->extractDatesFromString($base);
        $dateStart = $dates[0] ?? null;
        $dateEnd = $dates[count($dates) - 1] ?? $dateStart;

        // 2) Kode whitelist — prioritaskan segmen yang dipisah "---" (paling presisi),
        //    fallback ke token angka terpanjang yang cocok (hindari angka tanggal 5/2026 dll).
        $wlCode = null;
        $parts = explode('---', $base);
        foreach ($parts as $part) {
            if ($whitelists->has($part)) {
                $wlCode = $part;
                break;
            }
        }
        if (! $wlCode && preg_match_all('/\d+/', $base, $m)) {
            $tokens = $m[0];
            usort($tokens, fn ($a, $b) => strlen($b) <=> strlen($a));
            foreach ($tokens as $token) {
                if ($whitelists->has($token)) {
                    $wlCode = $token;
                    break;
                }
            }
        }

        if (! $wlCode) {
            return [
                'file' => $name,
                'ok' => false,
                'error' => 'Kode whitelist tidak ditemukan di nama file. Pastikan nama file memuat kode whitelist milik Anda (mis. OO---13722---...).',
            ];
        }

        $wl = $whitelists[$wlCode];

        // 3) Baca isi Excel
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $rows = $reader->load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return ['file' => $name, 'ok' => false, 'error' => 'Gagal membaca file Excel: '.$e->getMessage()];
        }

        if (empty($rows)) {
            return ['file' => $name, 'ok' => false, 'error' => 'File Excel kosong.'];
        }

        // 4) Deteksi baris header + kolom yang dibutuhkan
        $colCampaign = $colAmount = $colDateStart = $colDateEnd = null;
        $headerRow = -1;
        foreach ($rows as $i => $row) {
            $vals = array_map(fn ($v) => mb_strtolower(trim((string) ($v ?? ''))), $row);
            $joined = implode(' ', $vals);
            if (! str_contains($joined, 'kampanye') && ! str_contains($joined, 'campaign')) {
                continue;
            }
            $headerRow = $i;
            foreach ($vals as $ci => $lv) {
                if ($colCampaign === null && (str_contains($lv, 'kampanye') || str_contains($lv, 'campaign'))) {
                    $colCampaign = $ci;
                }
                if ($colAmount === null && (str_contains($lv, 'dibelanjakan') || str_contains($lv, 'amount spent') || str_contains($lv, 'spend'))) {
                    $colAmount = $ci;
                }
                if ($colDateStart === null && (str_contains($lv, 'tanggal mulai') || str_contains($lv, 'date start') || str_contains($lv, 'tanggal'))) {
                    $colDateStart = $ci;
                }
                if ($colDateEnd === null && (str_contains($lv, 'tanggal selesai') || str_contains($lv, 'date end'))) {
                    $colDateEnd = $ci;
                }
            }
            break;
        }

        if ($headerRow < 0 || $colCampaign === null || $colAmount === null) {
            return [
                'file' => $name,
                'ok' => false,
                'error' => 'Kolom "Nama Kampanye" dan "Jumlah yang dibelanjakan" tidak ditemukan di sheet pertama. Pastikan file adalah export Meta Ads Manager dengan kolom default.',
            ];
        }

        // 5) Iterasi baris data → pecah per tanggal.
        //    • Baris dengan tanggal mulai == selesai → 1 tanggal (breakdown harian).
        //    • Baris yang mencakup rentang (mulai ≠ selesai) → dibagi rata (pro-rata) per hari.
        //    • Tanpa kolom tanggal yang terbaca → pakai rentang dari nama file.
        $fallbackStart = $dateStart ?: now()->format('Y-m-d');
        $fallbackEnd   = $dateEnd   ?: $fallbackStart;
        $byDate = [];     // tgl => [product_id => total spending]
        $unmatched = [];  // kampanye yang produknya tidak dikenali
        $usedFallback = false;
        $prorated = false;

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $cName = trim((string) ($row[$colCampaign] ?? ''));
            if ($cName === '') {
                continue;
            }

            $amount = $this->parseAmount($row[$colAmount] ?? null);
            if ($amount <= 0) {
                continue;
            }

            // Rentang tanggal baris: dari kolom tanggal, fallback rentang nama file
            $dStart = $colDateStart !== null ? $this->parseCellDate($row[$colDateStart] ?? null) : null;
            $dEnd   = $colDateEnd   !== null ? $this->parseCellDate($row[$colDateEnd] ?? null) : null;
            if (! $dStart) {
                $dStart = $fallbackStart;
                $usedFallback = true;
            }
            if (! $dEnd) {
                // Tanpa tanggal selesai → pakai akhir rentang dari nama file
                $dEnd = $fallbackEnd;
            }

            $start = new \DateTimeImmutable($dStart);
            $end   = new \DateTimeImmutable($dEnd);
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }
            $days = max(1, $start->diff($end)->days + 1);
            $perDay = round($amount / $days, 2);

            $prod = $this->matchProduct($cName, $products);
            if (! $prod) {
                $unmatched[] = ['campaign' => $cName, 'spending' => $amount];
                continue;
            }

            if ($days > 1) {
                $prorated = true;
            }

            for ($d = 0; $d < $days; $d++) {
                $tgl = $start->modify('+'.$d.' day')->format('Y-m-d');
                // Hari terakhir menampung sisa pembulatan agar total baris tetap presisi
                $val = ($d === $days - 1) ? round($amount - $perDay * ($days - 1), 2) : $perDay;
                $byDate[$tgl][$prod->id] = ($byDate[$tgl][$prod->id] ?? 0) + $val;
            }
        }

        // 6) Susun groups per tanggal
        $groups = [];
        foreach ($byDate as $tgl => $prods) {
            $plist = [];
            $total = 0;
            foreach ($prods as $pid => $sp) {
                $p = $products->firstWhere('id', $pid);
                if (! $p) {
                    continue;
                }
                $plist[] = [
                    'product_id' => $pid,
                    'product_name' => $p->name.' ('.$p->code.')',
                    'spending' => round($sp, 2),
                ];
                $total += $sp;
            }
            usort($plist, fn ($a, $b) => strcmp($a['product_name'], $b['product_name']));
            $groups[] = ['tanggal' => $tgl, 'products' => $plist, 'total' => round($total, 2)];
        }
        usort($groups, fn ($a, $b) => strcmp($a['tanggal'], $b['tanggal']));

        // Peringatan bila rentang multi-hari tapi ada baris yang jatuh ke tanggal fallback
        $warning = null;
        if ($usedFallback && $dateStart && $dateEnd && $dateStart !== $dateEnd) {
            $warning = "Rentang file {$dateStart} s/d {$dateEnd}, tetapi sebagian baris tidak memiliki tanggal yang terbaca — spending baris itu dibagi rata per hari dalam rentang. Ubah tanggal di preview bila perlu.";
        }

        return [
            'file' => $name,
            'ok' => true,
            'third_party' => $this->thirdPartyFromFilename($base),
            'whitelist' => ['id' => $wl->id, 'nama' => $wl->nama, 'kode' => $wl->kode, 'platform' => $wl->platform],
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'groups' => $groups,
            'unmatched' => $unmatched,
            'warning' => $warning,
            'prorated' => $prorated,
        ];
    }

    /** Token pertama sebelum separator "---" = third party (mis. OO). */
    private function thirdPartyFromFilename(string $base): ?string
    {
        $parts = explode('---', $base);

        return $parts[0] !== '' ? trim($parts[0]) : null;
    }

    /** Cari semua tanggal berformat d-Mon-yyyy (Indonesia/Inggris, dengan spasi atau tanda hubung) di sebuah string. */
    private function extractDatesFromString(string $s): array
    {
        $out = [];
        if (preg_match_all('/(\d{1,2})[-\s.\/]+([A-Za-z]{3,9})[-\s.\/]+(\d{2,4})/', $s, $m, PREG_SET_ORDER)) {
            foreach ($m as $g) {
                $mon = mb_strtolower($g[2]);
                if (! isset(self::BULAN[$mon])) {
                    continue;
                }
                $day = (int) $g[1];
                if ($day < 1 || $day > 31) {
                    continue;
                }
                $year = (int) $g[3];
                if ($year < 100) {
                    $year += 2000;
                }
                $out[] = sprintf('%04d-%s-%02d', $year, self::BULAN[$mon], $day);
            }
        }

        return array_values(array_unique($out));
    }

    /** Normalisasi nominal: "1.234.567", "1234,56", "Rp 1.500", dsb → float. */
    private function parseAmount($v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        $s = trim((string) $v);
        $s = preg_replace('/[^\d.,\-]/', '', $s);
        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        // Titik + koma → titik ribuan, koma desimal (1.234,56 → 1234.56)
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } elseif (str_contains($s, ',')) {
            // Hanya koma: 1.000-an → ribuan; 12,5 → desimal
            if (preg_match('/,\d{3}$/', $s) && strlen($s) > 5) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        }

        return (float) $s;
    }

    /** Konversi sel tanggal (Excel serial number atau string) → Y-m-d. */
    private function parseCellDate($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        // Serial number Excel (hari sejak 1899-12-30)
        if (is_numeric($v)) {
            $n = (int) $v;
            if ($n > 20000 && $n < 60000) {
                return date('Y-m-d', ($n - 25569) * 86400);
            }

            return null;
        }

        $s = trim((string) $v);
        $dates = $this->extractDatesFromString($s);
        if ($dates) {
            return $dates[0];
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $s);
            if ($d && $d->format($fmt) === $s) {
                return $d->format('Y-m-d');
            }
        }

        $ts = strtotime($s);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * Cocokkan nama kampanye → produk.
     *
     * Urutan pencocokan (11 Agustus 2026):
     *   1) TOKEN UTUH — nama kampanye dibaca SELURUHNYA, dipecah per tanda "-".
     *      Semua advertiser memakai penanda "-" di kiri-kanan kode produk dengan
     *      posisi bebas (contoh: "INIT - 11/8/26 - KBJ - 1" → token "KBJ").
     *      Sufiks varian "+..." dibuang (mis. token "ksp+1.50" → "ksp") agar
     *      cocok dengan kode master. Token pertama yang cocok = produknya.
     *   2) PREFIX — kode di awal nama kampanye (format lama, kompatibel).
     *   3) CONTAINS — fallback terakhir (kode muncul di mana pun).
     */
    private function matchProduct(string $campaignName, $products): ?Product
    {
        $name = mb_strtolower(trim($campaignName));

        // ── 1) Token utuh (kode diapit "-", posisi bebas) ──
        $tokens = preg_split('/\s*-\s*/', $name);
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            $base = explode('+', $token)[0];
            if ($base === '') {
                continue;
            }
            foreach ($products as $p) {
                $kode = mb_strtolower(trim($p->code ?? ''));
                if ($kode !== '' && $base === $kode) {
                    return $p;
                }
            }
        }

        // ── 2) Prefix: kode di awal nama (format lama) ──
        foreach ($products as $p) {
            $kode = mb_strtolower(trim($p->code ?? ''));
            if ($kode !== '' && mb_strpos($name, $kode) === 0) {
                return $p;
            }
        }

        // ── 3) Contains: fallback terakhir ──
        foreach ($products as $p) {
            $kode = mb_strtolower(trim($p->code ?? ''));
            if ($kode !== '' && mb_strpos($name, $kode) !== false) {
                return $p;
            }
        }

        return null;
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

        // Batasi cakupan: advertiser hanya miliknya sendiri; CS hanya milik advertiser yang diampu
        $query = SpendingHarian::with('whitelist')->whereIn('id', $ids);
        if ($user->hasRole('advertiser')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('cs')) {
            $query->where('user_id', $user->advertiser_id);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang valid untuk dihapus.');
        }

        // ─── Notifikasi ke pemilik whitelist bila yang menghapus adalah CS (dedup per owner) ──
        $notifiedOwners = [];
        foreach ($rows as $spending) {
            $wl = $spending->whitelist;
            if ($user->hasRole('cs') && $wl && $wl->user_id !== $user->id
                && ! in_array($wl->user_id, $notifiedOwners, true)) {
                $notifiedOwners[] = $wl->user_id;
                $this->notifyWhitelistOwner($spending);
            }
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $spending) {
                $spending->delete();
            }
        });

        // ─── Rekalkulasi total_spending whitelist yang terdampak (batch) ──
        $this->recalculateWhitelistTotals($rows->pluck('whitelist_id'));

        return back()->with('success', $rows->count().' data spending berhasil dihapus.');
    }

    // ─── Bulk Update (edit massal spending/lead/paid via centang) ─

    public function bulkUpdate(Request $request): RedirectResponse
    {
        // Payload per baris: items[] = [{id, spending, lead, paid}, ...]
        // (nilai tiap baris bisa berbeda — diisi satu per satu di modal bulk edit)
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.spending' => ['required', 'numeric', 'min:0'],
            'items.*.lead' => ['required', 'integer', 'min:0'],
            'items.*.paid' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();

        // ── Batasi cakupan: advertiser hanya miliknya; CS hanya milik advertiser yang diampu ──
        $ids = array_map('intval', array_column($validated['items'], 'id'));
        $query = SpendingHarian::with('whitelist')->whereIn('id', $ids);
        if ($user->hasRole('advertiser')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('cs')) {
            $query->where('user_id', $user->advertiser_id);
        }
        $rows = $query->get()->keyBy('id');

        if ($rows->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang valid untuk diperbarui.');
        }

        $updated = [];

        DB::transaction(function () use ($validated, $rows, &$updated) {
            foreach ($validated['items'] as $item) {
                $row = $rows->get((int) $item['id']);
                if (! $row) {
                    continue; // di luar hak akses / tidak ditemukan → dilewati
                }

                $data = [
                    'spending' => $item['spending'],
                    'lead' => $item['lead'],
                    'paid' => $item['paid'],
                ];
                SpendingHarian::computeMetrics($data);
                $row->update($data);
                $updated[] = $row;
            }
        });

        if ($updated === []) {
            return back()->with('error', 'Tidak ada data yang valid untuk diperbarui.');
        }

        // ─── Notifikasi ke pemilik whitelist bila yang mengubah adalah CS
        //     (dedup per owner, SETELAH commit agar gagal notif tidak menggagalkan update) ──
        $notifiedOwners = [];
        foreach ($updated as $row) {
            $wl = $row->whitelist;
            if ($user->hasRole('cs') && $wl && $wl->user_id !== $user->id
                && ! in_array($wl->user_id, $notifiedOwners, true)) {
                $notifiedOwners[] = $wl->user_id;
                $this->notifyWhitelistOwner($row);
            }
        }

        // ─── Rekalkulasi total_spending whitelist yang terdampak (batch) ──
        $this->recalculateWhitelistTotals(collect($updated)->pluck('whitelist_id'));

        $skipped = count($validated['items']) - count($updated);
        $message = count($updated).' data spending berhasil diperbarui.';
        if ($skipped > 0) {
            $message .= " {$skipped} data dilewati (di luar hak akses Anda).";
        }

        return back()->with('success', $message);
    }

    /**
     * Rekalkulasi total_spending untuk sekumpulan whitelist secara batch
     * (1 query aggregate → map → update, sesuai pola AGENTS.md).
     */
    private function recalculateWhitelistTotals(iterable $whitelistIds): void
    {
        $wlIds = collect($whitelistIds)->unique()->values();

        if ($wlIds->isEmpty()) {
            return;
        }

        $totals = SpendingHarian::whereIn('whitelist_id', $wlIds)
            ->selectRaw('whitelist_id, COALESCE(SUM(spending),0) as total')
            ->groupBy('whitelist_id')
            ->pluck('total', 'whitelist_id');

        Whitelist::whereIn('id', $wlIds)->get()->each(function ($wl) use ($totals) {
            $wl->update(['total_spending' => (float) ($totals[$wl->id] ?? 0)]);
        });
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
