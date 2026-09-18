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

        // Ambil data regional untuk target user di range tanggal — DUAL FASE
        $reports = RegionalReport::where('user_id', $targetUserId)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->orderBy('province')
            ->get();

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

        // ─── Dua matriks per fase: running (utama) & testing (baru) ───
        $matrix = $this->buildMatrix($masterProvinces, $allDates, $reports, RegionalReport::PHASE_RUNNING);
        $matrixTesting = $this->buildMatrix($masterProvinces, $allDates, $reports, RegionalReport::PHASE_TESTING);

        // Total per tanggal per fase (untuk alarm + grand total)
        $totalPerTanggal = $this->totalPerTanggal($masterProvinces, $allDates, $matrix);
        $totalPerTanggalTesting = $this->totalPerTanggal($masterProvinces, $allDates, $matrixTesting);

        // ─── Alarm DUAL FASE: bandingkan dengan Spending Harian per fase ────────
        // Regional running ↔ spending fase running (tanggal >= products.start_running)
        // Regional testing ↔ spending fase testing (start_running null ATAU tanggal < start_running)
        $spendingByPhase = $this->spendingTotalsByPhase($targetUserId, $dari, $sampai);
        $spendingTotals = $spendingByPhase['running'];
        $spendingTotalsTesting = $spendingByPhase['testing'];

        $discRunning = $this->comparePhase(
            $allDates, $totalPerTanggal, $spendingTotals, 'Regional', 'Spending'
        );
        $discTesting = $this->comparePhase(
            $allDates, $totalPerTanggalTesting, $spendingTotalsTesting, 'Regional Testing', 'Spending Testing'
        );

        $hasDiscrepancy = $discRunning['hasDiscrepancy'] || $discTesting['hasDiscrepancy'];
        $discrepancies = $discRunning['discrepancies'];
        $missingSpendingDates = $discRunning['missingSpendingDates'];
        $missingRegionalDates = $discRunning['missingRegionalDates'];
        // Fase testing: namespace terpisah agar banner bisa menampilkan keduanya
        $discrepanciesTesting = $discTesting['discrepancies'];
        $missingSpendingDatesTesting = $discTesting['missingSpendingDates'];
        $missingRegionalDatesTesting = $discTesting['missingRegionalDates'];

        $totalRegional = [
            'lead' => collect($totalPerTanggal)->sum('lead'),
            'paid' => collect($totalPerTanggal)->sum('paid'),
        ];
        $totalRegionalTesting = [
            'lead' => collect($totalPerTanggalTesting)->sum('lead'),
            'paid' => collect($totalPerTanggalTesting)->sum('paid'),
        ];

        $totalSpending = [
            'lead' => (int) $spendingTotals->sum('total_lead'),
            'paid' => (int) $spendingTotals->sum('total_paid'),
        ];
        $totalSpendingTesting = [
            'lead' => (int) $spendingTotalsTesting->sum('total_lead'),
            'paid' => (int) $spendingTotalsTesting->sum('total_paid'),
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
            'matrixTesting',
            'totalPerTanggal',
            'totalPerTanggalTesting',
            'totalRegional',
            'totalRegionalTesting',
            'totalSpending',
            'totalSpendingTesting',
            'hasDiscrepancy',
            'discrepancies',
            'missingSpendingDates', 'missingRegionalDates',
            'discrepanciesTesting',
            'missingSpendingDatesTesting', 'missingRegionalDatesTesting',
            'dari',
            'sampai',
            'advertisers',
            'targetUserId',
            'hasAssignedCs',
        ));
    }

    /**
     * Matriks [province][tanggal] untuk satu fase regional.
     */
    private function buildMatrix(array $masterProvinces, array $allDates, $reports, string $phase): array
    {
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

        foreach ($reports->where('ad_phase', $phase)->groupBy('tanggal') as $tanggal => $items) {
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

        return $matrix;
    }

    /**
     * Total lead/paid per tanggal dari matriks (untuk alarm & grand total).
     */
    private function totalPerTanggal(array $masterProvinces, array $allDates, array $matrix): array
    {
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

        return $totalPerTanggal;
    }

    /**
     * Total spending per tanggal DUAL FASE (1 query gabungan, dipisah via CASE).
     * running: tanggal >= products.start_running (start_running wajib terisi)
     * testing: start_running null ATAU tanggal < products.start_running
     */
    private function spendingTotalsByPhase(int $userId, string $dari, string $sampai): array
    {
        $rows = SpendingHarian::where('user_id', $userId)
            ->whereBetween('spending_harians.tanggal', [$dari, $sampai])
            ->join('products', 'products.id', '=', 'spending_harians.product_id')
            ->selectRaw("spending_harians.tanggal,
                CASE WHEN products.start_running IS NOT NULL
                     AND spending_harians.tanggal >= products.start_running
                     THEN 1 ELSE 0 END as is_running,
                COALESCE(SUM(`lead`), 0) as total_lead,
                COALESCE(SUM(paid), 0) as total_paid")
            ->groupBy('spending_harians.tanggal', 'is_running')
            ->get();

        $byPhase = [
            'running' => collect(),
            'testing' => collect(),
        ];
        foreach ($rows as $row) {
            $tglKey = substr((string) $row->tanggal, 0, 10);
            $phase = ((int) $row->is_running) === 1 ? 'running' : 'testing';
            $byPhase[$phase][$tglKey] = $row;
        }

        return $byPhase;
    }

    /**
     * Bandingkan regional vs spending untuk satu fase → struktur banner.
     */
    private function comparePhase(array $allDates, array $totalPerTanggal, $spendingTotals, string $regLabel, string $spLabel): array
    {
        $hasDiscrepancy = false;
        $discrepancies = [];
        $missingSpendingDates = [];
        $missingRegionalDates = [];

        foreach ($allDates as $date) {
            $regLead = $totalPerTanggal[$date]['lead'];
            $regPaid = $totalPerTanggal[$date]['paid'];
            $spLead = (int) ($spendingTotals[$date]->total_lead ?? 0);
            $spPaid = (int) ($spendingTotals[$date]->total_paid ?? 0);

            $hasReg = $regLead > 0 || $regPaid > 0;
            $hasSp = $spLead > 0 || $spPaid > 0;

            if ($hasReg && !$hasSp) {
                $hasDiscrepancy = true;
                $missingSpendingDates[$date] = true;
                continue;
            }
            if ($hasSp && !$hasReg) {
                $hasDiscrepancy = true;
                $missingRegionalDates[$date] = true;
                continue;
            }
            if ($regLead !== $spLead || $regPaid !== $spPaid) {
                $hasDiscrepancy = true;
                $discrepancies[$date] = [
                    'regional_lead' => $regLead,
                    'regional_paid' => $regPaid,
                    'spending_lead' => $spLead,
                    'spending_paid' => $spPaid,
                    'reg_label' => $regLabel,
                    'sp_label' => $spLabel,
                ];
            }
        }

        return compact('hasDiscrepancy', 'discrepancies', 'missingSpendingDates', 'missingRegionalDates');
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
            'total_testing_lead' => $preview['total_testing_lead'] ?? 0,
            'total_testing_paid' => $preview['total_testing_paid'] ?? 0,
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
            // DUAL FASE: 'running' (tabel utama) atau 'testing' (tabel kedua).
            // Tanpa key = 'running' (kompatibel dgn payload lama).
            'items.*.ad_phase' => ['nullable', 'string', 'in:running,testing'],
            'items.*.lead' => ['required', 'integer', 'min:0'],
            'items.*.paid' => ['required', 'integer', 'min:0'],

            // CS Stats (opsional)
            'cs_stats' => ['nullable', 'array'],
            'cs_stats.*.tanggal' => ['required_with:cs_stats', 'date'],
            'cs_stats.*.cs_panggilan' => ['required_with:cs_stats', 'string', 'max:100'],
            'cs_stats.*.ad_phase' => ['nullable', 'string', 'in:running,testing'],
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
                // Batch-load existing records untuk dates + user ini — keyed per
                // (tanggal|province|ad_phase) karena tiap fase punya barisnya sendiri.
                $dates = array_unique(array_column($items, 'tanggal'));
                $existingMap = RegionalReport::where('user_id', $targetUserId)
                    ->whereIn('tanggal', $dates)
                    ->keyedPhase();

                foreach ($items as $item) {
                    $data = [
                        'tanggal' => $item['tanggal'],
                        'user_id' => $targetUserId,
                        'province' => $item['province'],
                        'ad_phase' => ($item['ad_phase'] ?? null) === RegionalReport::PHASE_TESTING
                            ? RegionalReport::PHASE_TESTING
                            : RegionalReport::PHASE_RUNNING,
                        'lead' => (int) $item['lead'],
                        'paid' => (int) $item['paid'],
                    ];

                    RegionalReport::computeRatio($data);

                    $key = $item['tanggal'].'|'.$item['province'].'|'.$data['ad_phase'];
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
                        ->keyBy(fn ($s) => $s->tanggal->format('Y-m-d').'|'.$s->cs_panggilan.'|'.($s->ad_phase ?? RegionalCsStat::PHASE_RUNNING));

                    foreach ($csStats as $stat) {
                        $csPanggilan = trim($stat['cs_panggilan']);
                        if (empty($csPanggilan)) {
                            continue;
                        }

                        $csUser = $csUsers[$csPanggilan] ?? null;
                        $adPhase = ($stat['ad_phase'] ?? null) === RegionalCsStat::PHASE_TESTING
                            ? RegionalCsStat::PHASE_TESTING
                            : RegionalCsStat::PHASE_RUNNING;

                        $data = [
                            'tanggal' => $stat['tanggal'],
                            'user_id' => $targetUserId,
                            'cs_panggilan' => $csPanggilan,
                            'cs_user_id' => $csUser?->id,
                            'ad_phase' => $adPhase,
                            'lead' => (int) $stat['lead'],
                            'paid' => (int) $stat['paid'],
                        ];

                        $existing = $existingCsMap[date('Y-m-d', strtotime($stat['tanggal'])).'|'.$csPanggilan.'|'.$adPhase] ?? null;

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

        $regionalByPhase = RegionalReport::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get()
            ->groupBy('ad_phase');

        // Regional running ↔ spending fase running; testing ↔ testing.
        $spendingByPhase = $this->spendingTotalsPhaseSums($user->id, $dari, $sampai);

        $regionalRunning = $regionalByPhase->get(RegionalReport::PHASE_RUNNING, collect());
        $regionalTesting = $regionalByPhase->get(RegionalReport::PHASE_TESTING, collect());

        $regionalLead = (int) $regionalRunning->sum('lead');
        $regionalPaid = (int) $regionalRunning->sum('paid');
        $regionalTestingLead = (int) $regionalTesting->sum('lead');
        $regionalTestingPaid = (int) $regionalTesting->sum('paid');

        return response()->json([
            'has_discrepancy' => $regionalLead !== $spendingByPhase['running']['lead']
                || $regionalPaid !== $spendingByPhase['running']['paid']
                || $regionalTestingLead !== $spendingByPhase['testing']['lead']
                || $regionalTestingPaid !== $spendingByPhase['testing']['paid'],
            'regional' => ['lead' => $regionalLead, 'paid' => $regionalPaid],
            'spending' => [
                'lead' => $spendingByPhase['running']['lead'],
                'paid' => $spendingByPhase['running']['paid'],
            ],
            // DUAL FASE (18 Sep): angka testing utk badge/banner lebih presisi
            'regional_testing' => ['lead' => $regionalTestingLead, 'paid' => $regionalTestingPaid],
            'spending_testing' => [
                'lead' => $spendingByPhase['testing']['lead'],
                'paid' => $spendingByPhase['testing']['paid'],
            ],
        ]);
    }

    /**
     * Total lead/paid spending per fase (running/testing) untuk SATU user —
     * 1 query + klasifikasi via CASE (bukan 2 query terpisah).
     */
    private function spendingTotalsPhaseSums(int $userId, string $dari, string $sampai): array
    {
        $rows = SpendingHarian::where('user_id', $userId)
            ->whereBetween('spending_harians.tanggal', [$dari, $sampai])
            ->join('products', 'products.id', '=', 'spending_harians.product_id')
            ->selectRaw("CASE WHEN products.start_running IS NOT NULL
                     AND spending_harians.tanggal >= products.start_running
                     THEN 'running' ELSE 'testing' END as phase,
                COALESCE(SUM(`lead`), 0) as total_lead,
                COALESCE(SUM(paid), 0) as total_paid")
            ->groupBy('phase')
            ->get()
            ->keyBy('phase');

        return [
            'running' => [
                'lead' => (int) ($rows['running']->total_lead ?? 0),
                'paid' => (int) ($rows['running']->total_paid ?? 0),
            ],
            'testing' => [
                'lead' => (int) ($rows['testing']->total_lead ?? 0),
                'paid' => (int) ($rows['testing']->total_paid ?? 0),
            ],
        ];
    }
}
