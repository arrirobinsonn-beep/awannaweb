<?php

namespace App\Http\Controllers;

use App\Models\BonusAllocationSetting;
use App\Models\CsAssignment;
use App\Models\RegionalCsStat;
use App\Models\User;
use App\Services\BonusCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BonusAllocationController extends Controller
{
    public function __construct(
        private BonusCalculationService $bonusService
    ) {}

    public function index(Request $request): View
    {
        $period = $request->input('period', now()->format('Y-m'));

        $advertisers = User::where('role', 'advertiser')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'panggilan']);

        // Bonus real-time per advertiser
        $bonusData = $this->bonusService->calculateRealtime($period)
            ->keyBy('user_id');

        // Global settings: keuangan & admin
        $globalSettings = BonusAllocationSetting::global()
            ->get()
            ->keyBy('role');

        $keuanganPct = (float) ($globalSettings->get('keuangan')->percentage ?? 9);
        $adminPct = (float) ($globalSettings->get('admin')->percentage ?? 7);

        // Default adv & cs % (dari advertiser pertama, untuk display di global bar)
        $advPctDefault = 36;
        $csPctDefault = 48;

        // Keuangan & admin users (global)
        $keuanganUser = User::role('keuangan')->where('is_active', true)->first();
        $adminUser = User::role('admin')->where('is_active', true)->first();

        // Per-advertiser settings
        $advSettings = BonusAllocationSetting::whereNotNull('advertiser_id')
            ->get()
            ->groupBy('advertiser_id');

        // CS utama: dari cs_assignments bulan ini, fallback ke users.advertiser_id
        $bulan = $period;
        $assignments = CsAssignment::where('bulan', $bulan)
            ->get()
            ->keyBy('cs_user_id');
        $useAssignments = $assignments->isNotEmpty();

        // Semua CS aktif
        $allCs = User::role('cs')
            ->with('advertiser')
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // Pre-compute main CS IDs per advertiser (untuk fallback)
        $mainCsByAdv = [];
        if (!$useAssignments) {
            foreach ($allCs as $cs) {
                if ($cs->advertiser_id) {
                    $mainCsByAdv[$cs->advertiser_id][] = $cs->id;
                }
            }
        }

        // CS paid data dari regional_cs_stats — group by user_id + cs_panggilan (nama)
        $csPaidData = RegionalCsStat::whereBetween('tanggal', [
            $period.'-01',
            Carbon::parse($period.'-01')->endOfMonth()->format('Y-m-d'),
        ])
            ->selectRaw('user_id, cs_panggilan, SUM(paid) as total_paid')
            ->groupBy('user_id', 'cs_panggilan')
            ->get()
            ->groupBy('user_id')
            ->mapWithKeys(fn ($rows, $uid) => [
                $uid => collect($rows)->mapWithKeys(fn ($r) => [
                    strtoupper(trim($r->cs_panggilan)) => (int) $r->total_paid,
                ]),
            ]);

        // Build allocation per advertiser
        $teams = collect();
        $grandTotal = 0;

        foreach ($advertisers as $adv) {
            $bonus = $bonusData->get($adv->id);
            $potensiBonus = (float) ($bonus->potensi_bonus ?? 0);

            // Per-advertiser settings
            $advPct = 36;
            $csPct = 48;
            if (isset($advSettings[$adv->id])) {
                foreach ($advSettings[$adv->id] as $s) {
                    if ($s->role === 'advertiser') $advPct = (float) $s->percentage;
                    if ($s->role === 'cs') $csPct = (float) $s->percentage;
                }
            }

            // Team: CS utama + CS pengganti
            if ($useAssignments) {
                $mainCsIds = $assignments
                    ->filter(fn ($a) => $a->advertiser_id == $adv->id)
                    ->pluck('cs_user_id')
                    ->all();
            } else {
                $mainCsIds = $mainCsByAdv[$adv->id] ?? [];
            }
            $mainCs = $allCs->whereIn('id', $mainCsIds)->values();
            $guestCs = $allCs->reject(fn ($cs) => in_array($cs->id, $mainCsIds))->values();

            // CS paid data per advertiser
            $advCsPaid = $csPaidData->get($adv->id, collect());

            // Build rows for this team
            $members = collect();

            // Hitung total paid CS dulu (untuk Jumlah PaidAdvertiser)
            $totalPaidCsForTeam = 0;

            // CS utama rows
            foreach ($mainCs as $cs) {
                $csName = strtoupper(trim($cs->panggilan ?: $cs->nama));
                $csPaid = (int) ($advCsPaid->get($csName) ?? 0);
                $totalPaidCsForTeam += $csPaid;
                $members->push((object) [
                    'role' => 'cs',
                    'name' => $cs->display_name,
                    'keterangan' => 'CS UTAMA',
                    'paid' => $csPaid,
                    'is_guest' => false,
                ]);
            }

            // CS pengganti rows
            foreach ($guestCs as $cs) {
                $csName = strtoupper(trim($cs->panggilan ?: $cs->nama));
                $csPaid = (int) ($advCsPaid->get($csName) ?? 0);
                $totalPaidCsForTeam += $csPaid;
                $members->push((object) [
                    'role' => 'cs',
                    'name' => $cs->display_name,
                    'keterangan' => 'CS PENGGANTI',
                    'paid' => $csPaid,
                    'is_guest' => true,
                ]);
            }

            // Advertiser row — paid = total paid semua CS di tim
            $adsPaid = $totalPaidCsForTeam;
            $members->prepend((object) [
                'role' => 'advertiser',
                'name' => $adv->display_name,
                'keterangan' => 'ADS UTAMA',
                'paid' => $adsPaid,
                'is_guest' => false,
            ]);

            // Keuangan row
            $members->push((object) [
                'role' => 'keuangan',
                'name' => $keuanganUser->display_name ?? '—',
                'keterangan' => 'KEUANGAN',
                'paid' => 0,
                'is_guest' => false,
            ]);

            // Admin row
            $members->push((object) [
                'role' => 'admin',
                'name' => $adminUser->display_name ?? '—',
                'keterangan' => 'ADMIN',
                'paid' => 0,
                'is_guest' => false,
            ]);

            // Calculate nominal & payment
            $nominalAds = $potensiBonus * $advPct / 100;
            $nominalCs = $potensiBonus * $csPct / 100;
            $nominalKeu = $potensiBonus * $keuanganPct / 100;
            $nominalAdmin = $potensiBonus * $adminPct / 100;

            // Total paid = total CS saja (adv paid = display saja, sama dengan total CS)
            $totalPaidCs = $members->where('role', 'cs')->sum('paid');
            $totalPaidTeam = $totalPaidCs;

            foreach ($members as $m) {
                if ($m->role === 'advertiser') {
                    $m->nominal = $nominalAds;
                    $m->pembagian = 100;
                    $m->payment = $nominalAds;
                } elseif ($m->role === 'cs') {
                    $m->nominal = $nominalCs;
                    $m->pembagian = $totalPaidCs > 0 ? ($m->paid / $totalPaidCs * 100) : 0;
                    $m->payment = $totalPaidCs > 0 ? ($m->paid / $totalPaidCs * $nominalCs) : 0;
                } elseif ($m->role === 'keuangan') {
                    $m->nominal = $nominalKeu;
                    $m->pembagian = 100;
                    $m->payment = $nominalKeu;
                } elseif ($m->role === 'admin') {
                    $m->nominal = $nominalAdmin;
                    $m->pembagian = 100;
                    $m->payment = $nominalAdmin;
                }
            }

            $totalPayment = $members->sum('payment');
            $grandTotal += $totalPayment;

            $teams->push((object) [
                'advertiser' => $adv,
                'potensi_bonus' => $potensiBonus,
                'ads_pct' => $advPct,
                'cs_pct' => $csPct,
                'members' => $members,
                'total_paid_team' => $totalPaidTeam,
                'total_payment' => $totalPayment,
                'formasi' => '1 ADS + '.max(1, $mainCs->count()).' CS',
            ]);
        }

        return view('bonus-allocation.index', compact(
            'teams', 'period', 'keuanganPct', 'adminPct', 'grandTotal', 'advPctDefault', 'csPctDefault'
        ));
    }

    /**
     * AJAX: update global settings (keuangan & admin %)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ads_pct' => 'required|numeric|min:0|max:100',
            'cs_pct' => 'required|numeric|min:0|max:100',
            'keuangan_pct' => 'required|numeric|min:0|max:100',
            'admin_pct' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            // Global: keuangan & admin
            BonusAllocationSetting::updateOrCreate(
                ['advertiser_id' => null, 'role' => 'keuangan'],
                ['percentage' => $validated['keuangan_pct']]
            );
            BonusAllocationSetting::updateOrCreate(
                ['advertiser_id' => null, 'role' => 'admin'],
                ['percentage' => $validated['admin_pct']]
            );
            // Default adv & cs (apply ke SEMUA advertiser yang ada)
            $advertiserIds = \App\Models\User::where('role', 'advertiser')->where('is_active', true)->pluck('id');
            foreach ($advertiserIds as $advId) {
                BonusAllocationSetting::updateOrCreate(
                    ['advertiser_id' => $advId, 'role' => 'advertiser'],
                    ['percentage' => $validated['ads_pct']]
                );
                BonusAllocationSetting::updateOrCreate(
                    ['advertiser_id' => $advId, 'role' => 'cs'],
                    ['percentage' => $validated['cs_pct']]
                );
            }
        });

        return response()->json(['success' => true]);
    }
}
