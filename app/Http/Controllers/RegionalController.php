<?php

namespace App\Http\Controllers;

use App\Models\CsAssignment;
use App\Models\Notification;
use App\Models\Product;
use App\Models\OrderOnlineContact;
use App\Models\RegionalCsStat;
use App\Models\RegionalReport;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Services\RegionalImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegionalController extends Controller
{
    protected RegionalImportService $importService;

    public function __construct(RegionalImportService $importService)
    {
        $this->importService = $importService;
    }

    // ─── Tampilkan Data Regional ──────────────────────────────

    public function index(Request $request): View
    {
        $user = Auth::user();

        // Date range: default bulan berjalan
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        // Ambil semua provinsi dari config
        $masterProvinces = config('regional.master_provinces', []);

        // ── CS & super admin bisa pilih advertiser ──
        $targetUserId = $user->id;
        $advertisers = collect();

        if ($user->hasRole(['cs', 'super_admin', 'owner'])) {
            if ($user->hasRole('cs') && $user->advertiser_id) {
                // CS hanya lihat advertiser yang menjadi atasan langsungnya
                $advertisers = User::where('id', $user->advertiser_id)
                    ->where('is_active', true)
                    ->get(['id', 'nama', 'panggilan', 'email']);
            } elseif ($user->hasRole('cs')) {
                // CS tanpa advertiser_id → tidak lihat data siapa pun
                $advertisers = collect();
            } else {
                $advertisers = User::role('advertiser')
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'panggilan', 'email']);
            }

            $requestedId = $request->input('user_id');
            if ($requestedId && $advertisers->contains('id', (int) $requestedId)) {
                $targetUserId = (int) $requestedId;
            } elseif ($advertisers->isNotEmpty()) {
                $targetUserId = $advertisers->first()->id;
            }
        }

        // Ambil data regional untuk target user di range tanggal
        $reports = RegionalReport::where('user_id', $targetUserId)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->orderBy('province')
            ->get()
            ->groupBy('tanggal');

        // Bangun semua tanggal dalam range
        $allDates = [];
        $start = Carbon::parse($dari);
        $end = Carbon::parse($sampai);
        while ($start->lte($end)) {
            $allDates[] = $start->format('Y-m-d');
            $start->addDay();
        }

        // Hapus tanggal yang masih di masa depan
        $today = now()->format('Y-m-d');
        $allDates = array_values(array_filter($allDates, fn ($d) => $d <= $today));

        // Matrix: [province][tanggal] = { lead, paid, ratio }
        $matrix = [];
        foreach ($masterProvinces as $province) {
            $matrix[$province] = [];
            foreach ($allDates as $date) {
                $matrix[$province][$date] = [
                    'lead' => 0,
                    'paid' => 0,
                    'ratio' => 0,
                ];
            }
        }

        // Isi matrix dengan data dari database
        foreach ($reports as $tanggal => $items) {
            $tglKey = substr((string) $tanggal, 0, 10);
            foreach ($items as $item) {
                if (isset($matrix[$item->province][$tglKey])) {
                    $matrix[$item->province][$tglKey] = [
                        'id' => (int) $item->id,
                        'lead' => (int) $item->lead,
                        'paid' => (int) $item->paid,
                        'ratio' => (float) $item->paid_ratio,
                    ];
                }
            }
        }

        // Total per tanggal (untuk alarm)
        $totalPerTanggal = [];
        foreach ($allDates as $date) {
            $tLead = 0;
            $tPaid = 0;
            foreach ($masterProvinces as $province) {
                $tLead += $matrix[$province][$date]['lead'];
                $tPaid += $matrix[$province][$date]['paid'];
            }
            $totalPerTanggal[$date] = [
                'lead' => $tLead,
                'paid' => $tPaid,
            ];
        }

        // ─── Alarm: bandingkan dengan Spending Harian ────────
        // Regional hanya memuat produk RUNNING → spending pembanding juga running saja
        $spendingTotals = SpendingHarian::where('user_id', $targetUserId)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->whereHas('product', fn ($q) => $q->where('ad_status', Product::AD_STATUS_RUNNING))
            ->selectRaw('tanggal, COALESCE(SUM(`lead`), 0) as total_lead, COALESCE(SUM(paid), 0) as total_paid')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal')
            ->mapWithKeys(fn ($item, $key) => [substr((string) $key, 0, 10) => $item]);

        $hasDiscrepancy = false;
        $discrepancies = [];
        $missingSpendingDates = [];

        foreach ($allDates as $date) {
            $regLead = $totalPerTanggal[$date]['lead'];
            $regPaid = $totalPerTanggal[$date]['paid'];
            $spLead = (int) ($spendingTotals[$date]->total_lead ?? 0);
            $spPaid = (int) ($spendingTotals[$date]->total_paid ?? 0);

            // Spending belum diisi sama sekali → "Data belum ditambahkan"
            if (($regLead > 0 || $regPaid > 0) && $spLead === 0 && $spPaid === 0) {
                $hasDiscrepancy = true;
                $missingSpendingDates[$date] = true;
                continue;
            }

            if ($regLead !== $spLead || $regPaid !== $spPaid) {
                $hasDiscrepancy = true;
                $discrepancies[$date] = [
                    'regional_lead' => $regLead,
                    'regional_paid' => $regPaid,
                    'spending_lead' => $spLead,
                    'spending_paid' => $spPaid,
                ];
            }
        }

        $totalRegional = [
            'lead' => collect($totalPerTanggal)->sum('lead'),
            'paid' => collect($totalPerTanggal)->sum('paid'),
        ];

        $totalSpending = [
            'lead' => (int) $spendingTotals->sum('total_lead'),
            'paid' => (int) $spendingTotals->sum('total_paid'),
        ];

        // ─── Guard tombol "Upload File Excel": advertiser wajib punya CS yang ditugaskan ──
        $hasAssignedCs = true;
        if ($user->hasRole('advertiser')) {
            $bulanSekarang = now()->format('Y-m');

            if (CsAssignment::where('bulan', $bulanSekarang)->exists()) {
                // Sumber utama: rotasi bulanan cs_assignments
                $hasAssignedCs = CsAssignment::where('bulan', $bulanSekarang)
                    ->where('advertiser_id', $user->id)
                    ->exists();
            } else {
                // Fallback data lama: snapshot users.advertiser_id
                $hasAssignedCs = User::where('advertiser_id', $user->id)
                    ->role('cs')
                    ->exists();
            }
        }

        return view('regional.index', compact(
            'masterProvinces',
            'allDates',
            'matrix',
            'totalPerTanggal',
            'totalRegional',
            'totalSpending',
            'hasDiscrepancy',
            'discrepancies',
            'missingSpendingDates',
            'dari',
            'sampai',
            'advertisers',
            'targetUserId',
            'hasAssignedCs',
        ));
    }

    // ─── Preview File (AJAX) ───────────────────────────────────

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv', 'max:10240'],
        ]);

        try {
            $result = $this->importService->parseExcel($request->file('file')->getPathname());
            $preview = $this->importService->previewData($result['data']);

            $masterProvinces = config('regional.master_provinces', []);

            // Pastikan semua provinsi dari master list ada di setiap tanggal (isi 0 jika tidak ada)
            foreach ($preview['by_date'] as $tgl => $items) {
                $existingProvinces = array_column($items, 'province');
                foreach ($masterProvinces as $prov) {
                    if (! in_array($prov, $existingProvinces)) {
                        $preview['by_date'][$tgl][] = [
                            'province' => $prov,
                            'lead' => 0,
                            'paid' => 0,
                            'paid_ratio' => 0,
                        ];
                    }
                }
                // Sort lagi setelah tambah yang 0
                usort($preview['by_date'][$tgl], fn ($a, $b) => strcmp($a['province'], $b['province']));
            }

            return response()->json([
                'success' => true,
                'data' => $preview,
                'errors' => $result['errors'],
                'total_raw_rows' => $result['total'],
                'skipped_testing' => $result['skipped_testing'] ?? 0,
                'phone_contacts' => $result['phone_contacts'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: '.$e->getMessage(),
            ], 422);
        }
    }

    // ─── Simpan Data dari Modal Preview (AJAX) ────────────────

    public function savePreview(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.tanggal' => ['required', 'date'],
            'items.*.province' => ['required', 'string', 'max:100'],
            'items.*.lead' => ['required', 'integer', 'min:0'],
            'items.*.paid' => ['required', 'integer', 'min:0'],

            // CS Stats (opsional)
            'cs_stats' => ['nullable', 'array'],
            'cs_stats.*.tanggal' => ['required_with:cs_stats', 'date'],
            'cs_stats.*.cs_panggilan' => ['required_with:cs_stats', 'string', 'max:100'],
            'cs_stats.*.lead' => ['required_with:cs_stats', 'integer', 'min:0'],
            'cs_stats.*.paid' => ['required_with:cs_stats', 'integer', 'min:0'],

            // Phone → CS mapping dari file yang sama (opsional)
            'phone_contacts' => ['nullable', 'array'],
            'phone_contacts.*.phone_normalized' => ['required_with:phone_contacts', 'string', 'max:30'],
            'phone_contacts.*.cs_name' => ['required_with:phone_contacts', 'string', 'max:100'],

            // CS bisa pilih target user
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        set_time_limit(120);

        $user = Auth::user();
        $items = $request->input('items');
        $csStats = $request->input('cs_stats', []);
        $phoneContacts = $request->input('phone_contacts', []);

        // CS bisa menyimpan atas nama advertiser tertentu
        $targetUserId = $user->id;
        if ($user->hasRole('cs') && $request->filled('target_user_id')) {
            $targetUserId = (int) $request->input('target_user_id');
        }

        try {
            $imported = 0;
            $updated = 0;
            $csSaved = 0;

            DB::transaction(function () use ($items, $csStats, $phoneContacts, $targetUserId, &$imported, &$updated, &$csSaved) {
                // Batch-load existing records untuk dates + user ini
                $dates = array_unique(array_column($items, 'tanggal'));
                $existingMap = RegionalReport::where('user_id', $targetUserId)
                    ->whereIn('tanggal', $dates)
                    ->get()
                    ->keyBy(fn ($r) => $r->tanggal->format('Y-m-d').'|'.$r->province);

                foreach ($items as $item) {
                    $data = [
                        'tanggal' => $item['tanggal'],
                        'user_id' => $targetUserId,
                        'province' => $item['province'],
                        'lead' => (int) $item['lead'],
                        'paid' => (int) $item['paid'],
                    ];

                    RegionalReport::computeRatio($data);

                    $key = $item['tanggal'].'|'.$item['province'];
                    $existing = $existingMap[$key] ?? null;

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        RegionalReport::create($data);
                        $imported++;
                    }
                }

                if (! empty($csStats)) {
                    // ─── Batch resolve CS user + existing stats ──────
                    $csPanggilans = collect($csStats)->pluck('cs_panggilan')
                        ->map(fn ($c) => trim($c))->filter()->unique()->values();
                    $csUsers = User::whereIn('panggilan', $csPanggilans)
                        ->role('cs')
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('panggilan');

                    $csDates = collect($csStats)->pluck('tanggal')
                        ->map(fn ($t) => date('Y-m-d', strtotime($t)))->unique()->values();
                    $existingCsMap = RegionalCsStat::where('user_id', $targetUserId)
                        ->whereIn('tanggal', $csDates)
                        ->get()
                        ->keyBy(fn ($s) => $s->tanggal->format('Y-m-d').'|'.$s->cs_panggilan);

                    foreach ($csStats as $stat) {
                        $csPanggilan = trim($stat['cs_panggilan']);
                        if (empty($csPanggilan)) {
                            continue;
                        }

                        $csUser = $csUsers[$csPanggilan] ?? null;

                        $data = [
                            'tanggal' => $stat['tanggal'],
                            'user_id' => $targetUserId,
                            'cs_panggilan' => $csPanggilan,
                            'cs_user_id' => $csUser?->id,
                            'lead' => (int) $stat['lead'],
                            'paid' => (int) $stat['paid'],
                        ];

                        $existing = $existingCsMap[date('Y-m-d', strtotime($stat['tanggal'])).'|'.$csPanggilan] ?? null;

                        if ($existing) {
                            $existing->update($data);
                        } else {
                            RegionalCsStat::create($data);
                        }
                        $csSaved++;
                    }
                }

                // ─── Simpan phone → CS mapping ─────────────────
                if (! empty($phoneContacts)) {
                    // Reset dulu data lama untuk advertiser ini
                    OrderOnlineContact::where('advertiser_id', $targetUserId)->delete();

                    $now = now();
                    $chunks = array_chunk($phoneContacts, 500);
                    foreach ($chunks as $chunk) {
                        $records = [];
                        foreach ($chunk as $pc) {
                            $records[] = [
                                'advertiser_id' => $targetUserId,
                                'phone_normalized' => $pc['phone_normalized'],
                                'cs_name' => $pc['cs_name'],
                                'order_id' => $pc['order_id'] ?? null,
                                'buyer_name' => $pc['buyer_name'] ?? null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                        OrderOnlineContact::insert($records);
                    }
                }
            });

            // ─── Notifikasi ke advertiser jika CS yang input ──
            if ($user->hasRole('cs') && $targetUserId !== $user->id) {
                Notification::create([
                    'user_id' => $targetUserId,
                    'from_user_id' => $user->id,
                    'type' => 'regional_correction',
                    'title' => 'Koreksi Data Regional oleh CS',
                    'message' => "CS {$user->panggilan} telah mengubah data regional. Silakan periksa dan sesuaikan data Anda.",
                    'data' => ['url' => route('regional.index')],
                ]);
            }

            $msg = "Berhasil: {$imported} baru, {$updated} diperbarui.";
            if ($csSaved > 0) {
                $msg .= " Data CS: {$csSaved} entri.";
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'cs_saved' => $csSaved,
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: '.$e->getMessage(),
            ], 500);
        }
    }

    // ─── Cek Tanggal yang Sudah Ada Data (AJAX) ─────────────────

    public function checkExistingDates(Request $request): JsonResponse
    {
        $request->validate([
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = Auth::user();
        $dates = $request->input('dates');

        // CS bisa cek untuk advertiser mana pun
        $targetUserId = $user->id;
        if ($user->hasRole('cs') && $request->filled('user_id')) {
            $targetUserId = (int) $request->input('user_id');
        }

        $existingDates = RegionalReport::where('user_id', $targetUserId)
            ->whereIn('tanggal', $dates)
            ->select('tanggal')
            ->distinct()
            ->get()
            ->pluck('tanggal')
            ->map(fn ($d) => $d instanceof Carbon ? $d->format('Y-m-d') : (string) $d)
            ->toArray();

        return response()->json([
            'has_existing' => count($existingDates) > 0,
            'existing_dates' => $existingDates,
        ]);
    }

    // ─── Update Satu Cell (AJAX) ───────────────────────────────

    public function updateCell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:regional_reports,id'],
            'lead' => ['required', 'integer', 'min:0'],
            'paid' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();

        $report = RegionalReport::findOrFail($validated['id']);

        // Advertiser hanya bisa edit data miliknya
        if ($user->hasRole('advertiser') && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data ini.',
            ], 403);
        }

        $data = [
            'lead' => (int) $validated['lead'],
            'paid' => (int) $validated['paid'],
        ];
        RegionalReport::computeRatio($data);
        $report->update($data);

        // ─── Notifikasi ke advertiser jika CS yang edit ───────
        if ($user->hasRole('cs') && $report->user_id !== $user->id) {
            Notification::create([
                'user_id' => $report->user_id,
                'from_user_id' => $user->id,
                'type' => 'regional_correction',
                'title' => 'Koreksi Data Regional oleh CS',
                'message' => "CS {$user->panggilan} telah mengubah data regional tanggal {$report->tanggal}. Silakan sesuaikan data Anda.",
                'data' => ['url' => route('regional.index')],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui.',
            'paid_ratio' => $data['paid_ratio'],
        ]);
    }

    // ─── Delete Satu Cell (AJAX) ───────────────────────────────

    public function deleteCell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:regional_reports,id'],
        ]);

        $user = Auth::user();

        $report = RegionalReport::findOrFail($validated['id']);

        // Advertiser hanya bisa hapus data miliknya
        if ($user->hasRole('advertiser') && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data ini.',
            ], 403);
        }

        // ─── Notifikasi ke advertiser jika CS yang hapus ──────
        $advertiserId = $report->user_id;
        $report->delete();

        if ($user->hasRole('cs') && $advertiserId !== $user->id) {
            Notification::create([
                'user_id' => $advertiserId,
                'from_user_id' => $user->id,
                'type' => 'regional_correction',
                'title' => 'Koreksi Data Regional oleh CS',
                'message' => "CS {$user->panggilan} telah menghapus data regional. Silakan periksa kembali data Anda.",
                'data' => ['url' => route('regional.index')],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    // ─── API: Cek Discrepancy (untuk badge navigasi) ───────────

    public function checkDiscrepancy(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['has_discrepancy' => false]);
        }

        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        $regionalLead = (int) RegionalReport::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->sum('lead');

        $regionalPaid = (int) RegionalReport::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->sum('paid');

        // Regional hanya memuat produk RUNNING → spending pembanding juga running saja
        $spendingLead = (int) SpendingHarian::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->whereHas('product', fn ($q) => $q->where('ad_status', Product::AD_STATUS_RUNNING))
            ->sum('lead');

        $spendingPaid = (int) SpendingHarian::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->whereHas('product', fn ($q) => $q->where('ad_status', Product::AD_STATUS_RUNNING))
            ->sum('paid');

        return response()->json([
            'has_discrepancy' => $regionalLead !== $spendingLead || $regionalPaid !== $spendingPaid,
            'regional' => ['lead' => $regionalLead, 'paid' => $regionalPaid],
            'spending' => ['lead' => $spendingLead, 'paid' => $spendingPaid],
        ]);
    }
}
