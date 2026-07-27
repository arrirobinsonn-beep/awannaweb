<?php

namespace App\Http\Controllers;

use App\Models\PaketTracking;
use App\Models\Product;
use App\Models\RegionalCsStat;
use App\Models\SpendingHarian;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Whitelist;
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
        if ($user->hasRole('cs')) {
            return $this->dashboardCs(request());
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
        $dari = request()->query('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = request()->query('sampai', now()->format('Y-m-d'));
        $paketStats = $this->getPaketStats($source, $dari, $sampai);

        $stokKritis = Product::where('stok', '<=', 10)->count();

        return view('dashboard.admin', compact('paketStats', 'stokKritis', 'source', 'dari', 'sampai'));
    }

    public function paketDetail(Request $request): JsonResponse
    {
        $kategori = $request->query('kategori');
        $source = $request->query('source', 'all');
        $dari = $request->query('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->query('sampai', now()->format('Y-m-d'));

        $query = PaketTracking::whereBetween('tanggal_pembuatan', [$dari, $sampai])
            ->orderByDesc('id')->limit(100);

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
                'catatan_kurir' => $pt->catatan_kurir,
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

    private function getPaketStats(string $source = 'all', string $dari = null, string $sampai = null): array
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
        if ($dari && $sampai) {
            $query = $query->whereBetween('tanggal_pembuatan', [$dari, $sampai]);
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

        $filtered = $allStatuses->filter(fn ($s) => $this->categorizeStatus($s) === $kategori);

        // 'retur' also includes proses_retur status
        if ($kategori === 'retur') {
            $filtered = $filtered->merge(
                $allStatuses->filter(fn ($s) => $this->categorizeStatus($s) === 'proses_retur')
            );
        }

        return $filtered->values()->toArray();
    }

    // ─── 4. CS Dashboard ────────────────────────────────────────

    public function dashboardCs(Request $request): View
    {
        $user = auth()->user();
        $namaCs = $user->panggilan ?? $user->nama;
        $advertiserId = $user->advertiser_id;
        $search = $request->query('search');
        $kategori = $request->query('kategori');
        $bulanIni = now()->format('Y-m');

        // Statistik per-CS dari RegionalCsStat (performa individu CS)
        $csStats = (object) [
            'total_lead' => 0,
            'total_paid' => 0,
        ];
        if ($advertiserId && $namaCs) {
            $s = RegionalCsStat::where('user_id', $advertiserId)
                ->where('cs_panggilan', $namaCs)
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month)
                ->selectRaw('COALESCE(SUM(lead),0) as total_lead,
                             COALESCE(SUM(paid),0) as total_paid')
                ->first();
            if ($s) $csStats = $s;
        }
        $paidRatio = $csStats->total_lead > 0
            ? round(($csStats->total_paid / $csStats->total_lead) * 100, 1)
            : 0;

        // Status counts per CS: terkirim / bermasalah / return
        $statusCounts = $this->getCsStatusCounts($namaCs);

        $totalOrder = PaketTracking::where('handle_by', $namaCs)->count();

        // Daftar order yang dihandle CS (pagination)
        $dataList = PaketTracking::where('handle_by', $namaCs)
            ->when($kategori, fn ($q) => $q->whereIn('status', $this->getStatusesForKategori($kategori)))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('awb', 'LIKE', "%{$search}%")
                   ->orWhere('status', 'LIKE', "%{$search}%")
                   ->orWhere('nama_produk', 'LIKE', "%{$search}%")
                   ->orWhere('no_telp', 'LIKE', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(50)
            ->through(function ($pt) {
                $phone = $pt->no_telp;
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if (substr($phone, 0, 1) === '0') {
                    $phone = '62' . substr($phone, 1);
                } elseif (substr($phone, 0, 2) !== '62') {
                    $phone = '62' . $phone;
                }
                $pt->wa_link = 'https://wa.me/' . $phone;
                return $pt;
            });

        $spending = $csStats;

        return view('dashboard.cs', compact(
            'user', 'namaCs', 'spending', 'paidRatio',
            'dataList', 'bulanIni', 'statusCounts', 'kategori', 'totalOrder',
        ));
    }

private function getCsStatusCounts(string $namaCs): array
    {
        $counts = \App\Models\PaketTracking::where('handle_by', $namaCs)
            ->selectRaw("CASE WHEN status IN ('retur','diretur','return','return to sender','rts') THEN 'retur'
                WHEN status LIKE '%retur%' AND status NOT LIKE '%selesai%' THEN 'proses_retur'
                WHEN status LIKE '%terkirim%' OR status LIKE '%diterima%' OR status LIKE '%selesai%' OR status = 'delivered' THEN 'terkirim'
                WHEN status LIKE '%kirim%' OR status LIKE '%pengiriman%' OR status LIKE '%pickup%' OR status LIKE '%konfirmasi%' OR status LIKE '%confirmed%' OR status LIKE '%pending%' OR status = 'proses' OR status = 'dikirim' OR status = 'menunggu pickup' THEN 'proses_kirim'
                WHEN status LIKE '%gagal%' OR status LIKE '%batal%' OR status LIKE '%cancel%' OR status LIKE '%bermasalah%' OR status LIKE '%error%' THEN 'bermasalah'
                ELSE 'proses_kirim' END as kategori, COUNT(*) as total")
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->toArray();

        return [
            'proses_kirim' => (int) ($counts['proses_kirim'] ?? 0),
            'terkirim' => (int) ($counts['terkirim'] ?? 0),
            'bermasalah' => (int) ($counts['bermasalah'] ?? 0),
            'retur' => (int) ($counts['retur'] ?? 0) + (int) ($counts['proses_retur'] ?? 0),
        ];
    }

    public function csSearchAwb(Request $request): JsonResponse
    {
        $user = auth()->user();
        $namaCs = $user->panggilan ?? $user->nama;
        $awb = $request->query('awb');
        $noTelp = $request->query('no_telp');

        if (empty($awb) && empty($noTelp)) {
            return response()->json(['success' => false, 'message' => 'Masukkan kata kunci', 'records' => [], 'total' => 0]);
        }

        $query = PaketTracking::where('handle_by', $namaCs);

        if (!empty($awb) && !empty($noTelp)) {
            $norm = \App\Services\OrderOnlineImportService::normalizePhone($noTelp);
            $query = $query->where(function ($q) use ($awb, $norm, $noTelp) {
                $q->where(function ($q2) use ($norm, $noTelp) {
                    $q2->where('no_telp', 'LIKE', '%' . $norm . '%')
                       ->orWhere('no_telp', 'LIKE', '%' . $noTelp . '%');
                })->orWhere('awb', 'LIKE', '%' . $awb . '%');
            });
        } elseif (!empty($awb)) {
            $query = $query->where('awb', 'LIKE', '%' . $awb . '%');
        } elseif (!empty($noTelp)) {
            $norm = \App\Services\OrderOnlineImportService::normalizePhone($noTelp);
            $query = $query->where(function ($q) use ($norm, $noTelp) {
                $q->where('no_telp', 'LIKE', '%' . $norm . '%')
                  ->orWhere('no_telp', 'LIKE', '%' . $noTelp . '%');
            });
        }

        $records = $query->orderByDesc('id')->limit(50)->get()->map(fn ($pt) => [
            'awb' => $pt->awb,
            'status' => $pt->status,
            'catatan_kurir' => $pt->catatan_kurir,
            'kurir' => $pt->kurir,
            'tanggal' => $pt->tanggal_pembuatan?->format('d/m/Y'),
            'nama_produk' => $pt->nama_produk,
            'nama_shopper' => $pt->nama_shopper,
            'kota' => $pt->kota,
            'harga' => $pt->harga_setelah_diskon,
            'no_telp' => $pt->no_telp,
        ]);

        return response()->json([
            'success' => true,
            'records' => $records,
            'total' => $records->count(),
        ]);
    }

    // ─── 5. Keuangan ───────────────────────────────────────────

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
