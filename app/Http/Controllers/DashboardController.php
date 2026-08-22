<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShippingOrder;
use App\Models\SpendingHarian;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole('advertiser')) {
            return $this->dashboardAdvertiser($user);
        }
        if ($user->hasRole('keuangan')) {
            return $this->dashboardKeuangan($user);
        }

        return $this->dashboardGeneral($user);
    }

    // ─── General (owner / super_admin / mentor / admin / cs) ───

    private function dashboardGeneral($user): View
    {
        $today = now()->format('Y-m-d');

        // ── Kartu operasional "hari ini" (query agregat, anti N+1) ──
        $stokHariIni = StockMovement::where('date', $today)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $orderHariIni = ShippingOrder::processed()
            ->where('created_at', '>=', $today)
            ->where('created_at', '<', now()->addDay()->format('Y-m-d'))
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN awb IS NOT NULL AND awb != \'\' THEN 1 ELSE 0 END) as resi,
                SUM(CASE WHEN payment_method = \'cod\' THEN 1 ELSE 0 END) as cod,
                SUM(CASE WHEN payment_method = \'bank_transfer\' THEN 1 ELSE 0 END) as bank_transfer')
            ->first();

        $opsHariIni = [
            'keluar' => (int) ($stokHariIni->keluar ?? 0),
            'masuk' => (int) ($stokHariIni->masuk ?? 0),
            'resi' => (int) ($orderHariIni->resi ?? 0),
            'cod' => (int) ($orderHariIni->cod ?? 0),
            'bank_transfer' => (int) ($orderHariIni->bank_transfer ?? 0),
        ];

        $stats = [
            'total_supplier' => Supplier::aktif()->count(),
            'total_produk' => Product::aktif()->count(),
            'total_user' => User::where('is_active', true)->count(),
            'total_whitelist' => Whitelist::aktif()->count(),
        ];

        $spendingHariIni = SpendingHarian::whereDate('tanggal', $today)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingBulanIni = SpendingHarian::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $chartSpending = SpendingHarian::selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', now()->subDays(13)->format('Y-m-d'))
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $topAdvertiser = SpendingHarian::with('user')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('user_id,
                             SUM(spending) as total_spending,
                             SUM(`lead`) as total_lead,
                             SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        return view('dashboard.general', compact(
            'stats', 'spendingHariIni', 'spendingBulanIni',
            'chartSpending', 'topAdvertiser', 'spendingPerWhitelist',
            'opsHariIni'
        ));
    }

    // ─── Advertiser ─────────────────────────────────────────

    private function dashboardAdvertiser($user): View
    {
        $today = now()->format('Y-m-d');
        $base = SpendingHarian::where('user_id', $user->id);

        $spendingHariIni = (clone $base)->whereDate('tanggal', $today)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingBulanIni = (clone $base)->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $chartSpending = (clone $base)
            ->selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', now()->subDays(13)->format('Y-m-d'))
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $myWhitelists = Whitelist::where('user_id', $user->id)->aktif()->get();

        $myRecent = SpendingHarian::with(['whitelist', 'product'])
            ->where('user_id', $user->id)
            ->latest('tanggal')->limit(8)->get();

        return view('dashboard.advertiser', compact(
            'user', 'spendingHariIni', 'spendingBulanIni',
            'chartSpending', 'myWhitelists', 'myRecent'
        ));
    }

    // ─── Keuangan ─────────────────────────────────────────

    private function dashboardKeuangan($user): View
    {
        $bulanIni = SpendingHarian::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $bulanLalu = SpendingHarian::whereYear('tanggal', now()->subMonth()->year)
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        $topAdvertiser = SpendingHarian::with('user')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('user_id,
                             SUM(spending) as total_spending,
                             SUM(`lead`) as total_lead,
                             SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        return view('dashboard.keuangan', compact(
            'bulanIni', 'bulanLalu', 'spendingPerWhitelist', 'topAdvertiser'
        ));
    }
}
