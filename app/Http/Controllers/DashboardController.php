<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Shipment;
use App\Models\ShippingOrder;
use App\Models\SpendingHarian;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TopUpProposal;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        if ($user->hasRole('advertiser')) {
            return $this->dashboardAdvertiser($user, $dari, $sampai);
        }
        if ($user->hasRole('keuangan')) {
            return $this->dashboardKeuangan($user, $dari, $sampai);
        }
        if ($user->hasRole(['owner', 'super_admin'])) {
            return $this->dashboardOwner($user, $dari, $sampai);
        }

        return $this->dashboardGeneral($user, $dari, $sampai);
    }

    // ═══════════════════════════════════════════════════════════
    // OWNER / SUPER_ADMIN — Semua fitur
    // ═══════════════════════════════════════════════════════════

    private function dashboardOwner($user, string $dari, string $sampai): View
    {
        $today      = now()->format('Y-m-d');
        $startMonth = $dari;
        $endDay     = $sampai.' 23:59:59';

        // ── 1) SALDO TOTAL ──
        $totalBalance = Account::aktif()->sum('current_balance');
        $accounts = Account::aktif()->get();

        // ── 2) REVENUE BULAN INI ──
        $revenueBulan = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('SUM(amount) as total, COUNT(*) as jumlah')
            ->first();

        // ── 3) SPENDING BULAN INI ──
        $spendingBulan = SpendingHarian::where('tanggal', '>=', $dari)
            ->where('tanggal', '<=', $sampai)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        // ── 4) PENDING APPROVALS ──
        $pendingTopUp = TopUpProposal::where('status', 'pending')->count();
        $pendingApproval = $pendingTopUp;
        $pendingBukti = BankTransfer::where('status', 'pending')->count();

        // ── 5) OPERASIONAL HARI INI ──
        $stokHariIni = StockMovement::where('date', $today)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $orderHariIni = ShippingOrder::processed()
            ->where('created_at', '>=', $today)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN awb IS NOT NULL AND awb != \'\' THEN 1 ELSE 0 END) as resi,
                SUM(CASE WHEN payment_method = \'cod\' THEN 1 ELSE 0 END) as cod,
                SUM(CASE WHEN payment_method = \'bank_transfer\' THEN 1 ELSE 0 END) as bank_transfer')
            ->first();

        $opsHariIni = [
            'keluar' => (int) ($stokHariIni->keluar ?? 0),
            'masuk'  => (int) ($stokHariIni->masuk ?? 0),
            'total'  => (int) ($orderHariIni->total ?? 0),
            'resi'   => (int) ($orderHariIni->resi ?? 0),
            'cod'    => (int) ($orderHariIni->cod ?? 0),
            'bank_transfer' => (int) ($orderHariIni->bank_transfer ?? 0),
        ];

        // ── 6) CHART DATA ──
        $chartRevenue30 = ShippingOrder::processed()
            ->selectRaw('DATE(created_at) as tanggal, SUM(amount) as total')
            ->where('created_at', '>=', $dari)
            ->where('created_at', '<=', $endDay)
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $chartSpending30 = SpendingHarian::selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', $dari)
            ->where('tanggal', '<=', $sampai)
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $chartStock14 = StockMovement::where('date', '>=', $dari)
            ->where('date', '<=', $sampai)
            ->selectRaw('date,
                SUM(CASE WHEN type=\'in\' THEN quantity ELSE 0 END) as masuk,
                SUM(CASE WHEN type=\'out\' THEN quantity ELSE 0 END) as keluar')
            ->groupBy('date')->orderBy('date')->get();

        $orderPerCourier = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('courier, COUNT(*) as jumlah')
            ->groupBy('courier')->orderByDesc('jumlah')->get();

        $orderPerPayment = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('payment_method, COUNT(*) as jumlah')
            ->groupBy('payment_method')->orderByDesc('jumlah')->get();

        // ── 7) TOP ADVERTISER & WHITELIST ──
        $topAdvertiser = SpendingHarian::with('user')
            ->where('tanggal', '>=', $dari)->where('tanggal', '<=', $sampai)
            ->selectRaw('user_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->where('tanggal', '>=', $dari)->where('tanggal', '<=', $sampai)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        // ── 8) RECENT DATA ──
        $recentOrders = ShippingOrder::with('importBatch')
            ->where('created_at', '>=', $dari)
            ->where('created_at', '<=', $endDay)
            ->latest('created_at')->limit(6)->get();

        $recentShipments = Shipment::where('created_date', '>=', $dari)
            ->where('created_date', '<=', $sampai)
            ->latest('created_date')->limit(6)->get();

        $recentPurchases = Purchase::with(['variant.product', 'creator'])
            ->latest()->limit(5)->get();

        // ── 9) LOW STOCK ──
        $lowStockProducts = Product::aktif()
            ->where('min_stock', '>', 0)
            ->with('variants')
            ->get()
            ->filter(fn($p) => $p->variants->sum('stock') <= $p->min_stock)
            ->values();

        // ── 10) MASTER STATS ──
        $stats = [
            'total_supplier'  => Supplier::aktif()->count(),
            'total_produk'    => Product::aktif()->count(),
            'total_user'      => User::where('is_active', true)->count(),
            'total_whitelist' => Whitelist::aktif()->count(),
        ];

        return view('dashboard.owner', compact(
            'totalBalance', 'accounts', 'revenueBulan', 'spendingBulan',
            'pendingApproval', 'pendingBukti',
            'opsHariIni',
            'chartRevenue30', 'chartSpending30', 'chartStock14',
            'orderPerCourier', 'orderPerPayment',
            'topAdvertiser', 'spendingPerWhitelist',
            'recentOrders', 'recentShipments', 'recentPurchases', 'lowStockProducts', 'stats', 'dari', 'sampai'
        ));
    }

    // ─── General (admin / mentor / cs) — Hanya operasional ───

    private function dashboardGeneral($user, string $dari, string $sampai): View
    {
        $today      = now()->format('Y-m-d');
        $startMonth = $dari;
        $endDay     = $sampai.' 23:59:59';

        $stokHariIni = StockMovement::where('date', $today)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $orderHariIni = ShippingOrder::processed()
            ->where('created_at', '>=', $today)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN awb IS NOT NULL AND awb != \'\' THEN 1 ELSE 0 END) as resi,
                SUM(CASE WHEN payment_method = \'cod\' THEN 1 ELSE 0 END) as cod,
                SUM(CASE WHEN payment_method = \'bank_transfer\' THEN 1 ELSE 0 END) as bank_transfer')
            ->first();

        $opsHariIni = [
            'keluar' => (int) ($stokHariIni->keluar ?? 0),
            'masuk'  => (int) ($stokHariIni->masuk ?? 0),
            'total'  => (int) ($orderHariIni->total ?? 0),
            'resi'   => (int) ($orderHariIni->resi ?? 0),
            'cod'    => (int) ($orderHariIni->cod ?? 0),
            'bank_transfer' => (int) ($orderHariIni->bank_transfer ?? 0),
        ];

        $revenueBulan = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('SUM(amount) as total, COUNT(*) as jumlah')
            ->first();

        $pendingTopUp = TopUpProposal::where('status', 'pending')->count();
        $pendingApproval = $pendingTopUp;

        $stats = [
            'total_supplier' => Supplier::aktif()->count(),
            'total_produk'   => Product::aktif()->count(),
        ];

        $chartRevenue30 = ShippingOrder::processed()
            ->selectRaw('DATE(created_at) as tanggal, SUM(amount) as total')
            ->where('created_at', '>=', $dari)
            ->where('created_at', '<=', $endDay)
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $orderPerCourier = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('courier, COUNT(*) as jumlah')
            ->groupBy('courier')->orderByDesc('jumlah')->get();

        $orderPerPayment = ShippingOrder::processed()
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endDay)
            ->selectRaw('payment_method, COUNT(*) as jumlah')
            ->groupBy('payment_method')->orderByDesc('jumlah')->get();

        $chartStock14 = StockMovement::where('date', '>=', $dari)
            ->where('date', '<=', $sampai)
            ->selectRaw('date,
                SUM(CASE WHEN type=\'in\' THEN quantity ELSE 0 END) as masuk,
                SUM(CASE WHEN type=\'out\' THEN quantity ELSE 0 END) as keluar')
            ->groupBy('date')->orderBy('date')->get();

        $recentOrders = ShippingOrder::with('importBatch')
            ->where('created_at', '>=', $dari)
            ->where('created_at', '<=', $endDay)
            ->latest('created_at')->limit(8)->get();

        $recentShipments = Shipment::where('created_date', '>=', $dari)
            ->where('created_date', '<=', $sampai)
            ->latest('created_date')->limit(8)->get();

        $recentPurchases = Purchase::with(['variant.product', 'creator'])
            ->latest()->limit(6)->get();

        $lowStockProducts = Product::aktif()
            ->where('min_stock', '>', 0)
            ->with('variants')
            ->get()
            ->filter(fn($p) => $p->variants->sum('stock') <= $p->min_stock)
            ->values();

        return view('dashboard.general', compact(
            'stats', 'opsHariIni', 'revenueBulan',
            'pendingApproval',
            'chartRevenue30',
            'chartStock14',
            'orderPerCourier', 'orderPerPayment',            'recentOrders', 'recentShipments', 'recentPurchases', 'lowStockProducts', 'dari', 'sampai'
        ));
    }

    // ─── Advertiser ─────────────────────────────────────────

    private function dashboardAdvertiser($user, string $dari, string $sampai): View
    {
        $today = now()->format('Y-m-d');
        $base = SpendingHarian::where('user_id', $user->id);

        $spendingHariIni = (clone $base)->whereDate('tanggal', $today)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingBulanIni = (clone $base)->where('tanggal', '>=', $dari)
            ->where('tanggal', '<=', $sampai)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $chartSpending = (clone $base)
            ->selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', $dari)
            ->where('tanggal', '<=', $sampai)
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $myWhitelists = Whitelist::where('user_id', $user->id)->aktif()->get();

        $myRecent = SpendingHarian::with(['whitelist', 'product'])
            ->where('user_id', $user->id)
            ->latest('tanggal')->limit(8)->get();

        return view('dashboard.advertiser', compact(
            'user', 'spendingHariIni', 'spendingBulanIni', 'dari', 'sampai',
            'chartSpending', 'myWhitelists', 'myRecent'
        ));
    }

    // ─── Keuangan ─────────────────────────────────────────

    private function dashboardKeuangan($user, string $dari, string $sampai): View
    {
        $bulanIni = SpendingHarian::where('tanggal', '>=', $dari)
            ->where('tanggal', '<=', $sampai)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $bulanLalu = SpendingHarian::whereYear('tanggal', now()->subMonth()->year)
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->where('tanggal', '>=', $dari)->where('tanggal', '<=', $sampai)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(`lead`) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        $topAdvertiser = SpendingHarian::with('user')
            ->where('tanggal', '>=', $dari)->where('tanggal', '<=', $sampai)
            ->selectRaw('user_id,
                SUM(spending) as total_spending,
                SUM(`lead`) as total_lead,
                SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        return view('dashboard.keuangan', compact(
            'bulanIni', 'bulanLalu', 'spendingPerWhitelist', 'topAdvertiser', 'dari', 'sampai'
        ));
    }
}
