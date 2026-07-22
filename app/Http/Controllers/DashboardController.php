<?php

namespace App\Http\Controllers;

use App\Models\PaketTracking;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole(['owner', 'super_admin', 'mentor'])) {
            return $this->dashboardGeneral($user);
        }
        if ($user->hasRole('advertiser')) {
            return $this->dashboardAdvertiser($user);
        }
        if ($user->hasRole('admin')) {
            return $this->dashboardAdmin($user);
        }
        if ($user->hasRole('keuangan')) {
            return $this->dashboardKeuangan($user);
        }

        return $this->dashboardGeneral($user);
    }

    // ─── 1. General (owner / super_admin / mentor) ─────────────

    private function dashboardGeneral($user): View
    {
        $today = now()->format('Y-m-d');

        $stats = [
            'total_supplier' => Supplier::aktif()->count(),
            'total_produk' => Product::aktif()->count(),
            'total_user' => User::where('is_active', true)->count(),
            'total_whitelist' => Whitelist::aktif()->count(),
        ];

        // Kolom baru: spending, lead, paid
        $spendingHariIni = SpendingHarian::whereDate('tanggal', $today)
            ->selectRaw('SUM(spending) as total_spending,
                         SUM(lead) as total_lead,
                         SUM(paid) as total_paid')
            ->first();

        $spendingBulanIni = SpendingHarian::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending,
                         SUM(lead) as total_lead,
                         SUM(paid) as total_paid')
            ->first();

        // Chart 14 hari — hanya spending (tidak ada revenue lagi)
        $chartSpending = SpendingHarian::selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', now()->subDays(13)->format('Y-m-d'))
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        // Top advertiser bulan ini berdasarkan spending
        $topAdvertiser = SpendingHarian::with('user')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('user_id,
                         SUM(spending) as total_spending,
                         SUM(lead) as total_lead,
                         SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        // Spending per whitelist (pengganti per-platform)
        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        return view('dashboard.general', compact(
            'stats', 'spendingHariIni', 'spendingBulanIni',
            'chartSpending', 'topAdvertiser', 'spendingPerWhitelist'
        ));
    }

    // ─── 2. Advertiser ─────────────────────────────────────────

    private function dashboardAdvertiser($user): View
    {
        $today = now()->format('Y-m-d');
        $base = SpendingHarian::where('user_id', $user->id);

        $spendingHariIni = (clone $base)->whereDate('tanggal', $today)
            ->selectRaw('SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->first();

        $spendingBulanIni = (clone $base)->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->first();

        $chartSpending = (clone $base)
            ->selectRaw('DATE(tanggal) as tanggal, SUM(spending) as total_spending')
            ->where('tanggal', '>=', now()->subDays(13)->format('Y-m-d'))
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $myWhitelists = Whitelist::where('user_id', $user->id)->aktif()->get();

        // Data terbaru milik sendiri
        $myRecent = SpendingHarian::with(['whitelist', 'product'])
            ->where('user_id', $user->id)
            ->latest('tanggal')->limit(8)->get();

        return view('dashboard.advertiser', compact(
            'user', 'spendingHariIni', 'spendingBulanIni',
            'chartSpending', 'myWhitelists', 'myRecent'
        ));
    }

    // ─── 3. Admin ──────────────────────────────────────────────

    private function dashboardAdmin($user): View
    {
        $source = request()->query('source', 'all');
        $paketStats = $this->getPaketStats($source);

        $stokKritis = Product::where('stok', '<=', 10)->count();

        return view('dashboard.admin', compact('paketStats', 'stokKritis', 'source'));
    }

    public function paketDetail(Request $request): JsonResponse
    {
        $kategori = $request->query('kategori');
        $source = $request->query('source', 'all');

        $query = PaketTracking::orderByDesc('id')->limit(100);

        if ($source !== 'all') {
            $query = $query->whereHas('kirimanActual', fn ($q) => $q->where('dashboard', strtoupper($source)));
        }

        if ($kategori !== 'total_paket') {
            $statuses = $this->getStatusesForKategori($kategori);
            if (empty($statuses)) {
                return response()->json([
                    'success' => true,
                    'kategori' => $kategori,
                    'records' => [],
                    'total' => 0,
                ]);
            }
            $query = $query->whereIn('status', $statuses);
        }

        $records = $query->get()->map(fn ($pt) => [
                'awb' => $pt->awb,
                'kurir' => $pt->kurir,
                'status' => $pt->status,
                'tanggal' => $pt->tanggal_pembuatan?->format('d/m/Y'),
                'nama_produk' => $pt->nama_produk,
                'nama_shopper' => $pt->nama_shopper,
                'kota' => $pt->kota,
                'harga' => $pt->harga_setelah_diskon,
            ]);

        return response()->json([
            'success' => true,
            'kategori' => $kategori,
            'records' => $records,
            'total' => count($records),
        ]);
    }

    private function getPaketStats(string $source = 'all'): array
    {
        $kategoris = [
            'total_paket' => ['label' => 'Total Paket', 'icon' => '📦', 'color' => '#3b82f6'],
            'proses_retur' => ['label' => 'Proses Retur', 'icon' => '🔄', 'color' => '#f59e0b'],
            'retur' => ['label' => 'Retur', 'icon' => '↩️', 'color' => '#ef4444'],
            'proses_kirim' => ['label' => 'Proses Pengiriman', 'icon' => '🚚', 'color' => '#06b6d4'],
            'terkirim' => ['label' => 'Terkirim', 'icon' => '✅', 'color' => '#10b981'],
            'bermasalah' => ['label' => 'Bermasalah', 'icon' => '⛔', 'color' => '#dc2626'],
        ];

        $query = PaketTracking::query();
        if ($source !== 'all') {
            $query = $query->whereHas('kirimanActual', fn ($q) => $q->where('dashboard', strtoupper($source)));
        }
        $allStatuses = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [];
        foreach ($kategoris as $key => $meta) {
            $stats[$key] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'total' => 0,
            ];
        }
        $stats['total_paket']['total'] = $allStatuses->sum();

        foreach ($allStatuses as $status => $count) {
            $cat = $this->categorizeStatus($status);
            if (isset($stats[$cat])) {
                $stats[$cat]['total'] += $count;
            }
        }

        return $stats;
    }

    private function categorizeStatus(string $status): string
    {
        $s = strtolower(trim($status));

        if (in_array($s, ['retur', 'diretur', 'return', 'return to sender', 'rts'])) {
            return 'retur';
        }
        if (str_contains($s, 'retur') && ! str_contains($s, 'selesai')) {
            return 'proses_retur';
        }
        if (str_contains($s, 'terkirim')
            || str_contains($s, 'diterima')
            || str_contains($s, 'selesai')
            || $s === 'delivered'
        ) {
            return 'terkirim';
        }
        if (str_contains($s, 'kirim')
            || str_contains($s, 'pengiriman')
            || str_contains($s, 'pickup')
            || str_contains($s, 'konfirmasi')
            || str_contains($s, 'confirmed')
            || str_contains($s, 'pending')
            || $s === 'proses'
            || $s === 'dikirim'
            || $s === 'menunggu pickup'
        ) {
            return 'proses_kirim';
        }
        if (str_contains($s, 'gagal')
            || str_contains($s, 'batal')
            || str_contains($s, 'cancel')
            || str_contains($s, 'bermasalah')
            || str_contains($s, 'error')
        ) {
            return 'bermasalah';
        }

        return 'proses_kirim';
    }

    private function getStatusesForKategori(string $kategori): array
    {
        $allStatuses = PaketTracking::select('status')->distinct()->pluck('status');

        return $allStatuses->filter(fn ($s) => $this->categorizeStatus($s) === $kategori)->values()->toArray();
    }

    // ─── 4. Keuangan ───────────────────────────────────────────

    private function dashboardKeuangan($user): View
    {
        $bulanIni = SpendingHarian::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->first();

        $bulanLalu = SpendingHarian::whereYear('tanggal', now()->subMonth()->year)
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->selectRaw('SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->first();

        $topAdvertiser = SpendingHarian::with('user')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('user_id, SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->groupBy('user_id')->orderByDesc('total_spending')->limit(5)->get();

        $spendingPerWhitelist = SpendingHarian::with('whitelist')
            ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
            ->selectRaw('whitelist_id, SUM(spending) as total_spending, SUM(lead) as total_lead, SUM(paid) as total_paid')
            ->groupBy('whitelist_id')->orderByDesc('total_spending')->limit(6)->get();

        return view('dashboard.keuangan', compact(
            'bulanIni', 'bulanLalu', 'topAdvertiser', 'spendingPerWhitelist'
        ));
    }
}
