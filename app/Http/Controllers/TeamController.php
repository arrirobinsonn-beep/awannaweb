<?php

namespace App\Http\Controllers;

use App\Models\CsAssignment;
use App\Models\OrderOnlineContact;
use App\Models\RegionalCsStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * Tampilkan daftar CS yang menjadi tim dari advertiser yang sedang login,
     * atau info advertiser untuk CS.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->hasRole('cs')) {
            // CS lihat info advertiser tempat mereka bernaung
            $advertiser = $user->advertiser;
            $team = collect();
            if ($advertiser) {
                $team = User::where('advertiser_id', $advertiser->id)
                    ->with('roles')
                    ->orderBy('nama')
                    ->orderBy('email')
                    ->get();
            }

            return view('team.index', compact('team', 'user', 'advertiser'));
        }

        // Hanya advertiser & cs yang punya akses
        abort_unless($user->hasRole('advertiser'), 403, 'Halaman ini hanya untuk Advertiser.');

        // ─── Mekanisme rotasi CS bulanan ─────────────────────────────
        // Tampilkan SEMUA CS: CS Utama (yang dikhususkan untuk advertiser ini
        // pada BULAN BERJALAN) dan CS Tamu (seluruh CS lain yang masuk rotasi).
        $bulanSekarang = now()->format('Y-m');
        $assignmentsBulanIni = CsAssignment::where('bulan', $bulanSekarang)
            ->get(['cs_user_id', 'advertiser_id'])
            ->keyBy('cs_user_id');

        $allCs = User::role('cs')
            ->with('roles', 'advertiser')
            ->orderBy('nama')
            ->orderBy('email')
            ->get();

        if ($assignmentsBulanIni->isNotEmpty()) {
            // Mapping bulan berjalan dikelola lewat cs_assignments (histori rotasi)
            $mainCsIds = $assignmentsBulanIni
                ->filter(fn ($a) => $a->advertiser_id == $user->id)
                ->pluck('cs_user_id');
            $mainCs = $allCs->whereIn('id', $mainCsIds)->values();
            $guestCs = $allCs->reject(fn ($cs) => $mainCsIds->contains($cs->id))->values();
        } else {
            // Fallback data lama: pakai snapshot users.advertiser_id
            $mainCs = $allCs->where('advertiser_id', $user->id)->values();
            $guestCs = $allCs->where('advertiser_id', '!=', $user->id)->values();
        }

        // Riwayat CS Utama untuk advertiser ini (modal "Riwayat CS Utama")
        $csHistory = CsAssignment::where('advertiser_id', $user->id)
            ->with('csUser')
            ->orderByDesc('bulan')
            ->get();

        return view('team.index', compact('mainCs', 'guestCs', 'user', 'csHistory'));
    }

    /**
     * Performa Tim — lihat lead/paid per CS per hari.
     * Data bersumber dari regional_cs_stats (hasil import Excel).
     */
    public function performance(Request $request): View
    {
        $user = auth()->user();
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        // Halaman ini hanya untuk Advertiser & CS
        abort_unless($user->hasRole(['advertiser', 'cs']), 403, 'Halaman ini hanya untuk Advertiser & CS.');

        // Penempatan CS Utama pada BULAN yang sedang dilihat (dari tanggal awal filter)
        $bulan = substr($dari, 0, 7);
        $assignments = CsAssignment::where('bulan', $bulan)
            ->with('advertiser')
            ->get(['id', 'cs_user_id', 'advertiser_id'])
            ->keyBy('cs_user_id');
        $useAssignments = $assignments->isNotEmpty();

        // Tentukan advertiser pemilik data (advertiser → dirinya; CS → atasan pada bulan itu)
        if ($user->hasRole('advertiser')) {
            $advertiserId = $user->id;
        } else {
            $advertiserId = $assignments[$user->id]->advertiser_id ?? $user->advertiser_id;
        }

        $stats = collect();
        $byDate = [];
        $totalPerCs = [];
        $mainMembers = collect();
        $guestMembers = collect();

        if ($advertiserId) {
            $stats = RegionalCsStat::with('csUser.advertiser')
                ->where('user_id', $advertiserId)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderBy('tanggal', 'asc')
                ->orderBy('cs_panggilan', 'asc')
                ->get();

            // Group by tanggal → CS
            foreach ($stats as $stat) {
                $tglKey = $stat->tanggal instanceof Carbon
                    ? $stat->tanggal->format('Y-m-d')
                    : substr((string) $stat->tanggal, 0, 10);
                $byDate[$tglKey][] = $stat;
            }

            // CS Utama = CS yang dikhususkan untuk advertiser ini pada bulan terpilih
            if ($useAssignments) {
                $mainCsIds = $assignments
                    ->filter(fn ($a) => $a->advertiser_id == $advertiserId)
                    ->pluck('cs_user_id');
                $mainMembers = User::whereIn('id', $mainCsIds)
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);
            } else {
                // Fallback data lama: snapshot users.advertiser_id
                $mainMembers = User::where('advertiser_id', $advertiserId)
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);
            }

            // Total per CS (untuk kartu statistik)
            foreach ($stats->groupBy('cs_panggilan') as $csName => $csStats) {
                $totalPerCs[$csName] = [
                    'lead' => $csStats->sum('lead'),
                    'paid' => $csStats->sum('paid'),
                ];
            }

            // ─── CS Tamu: SEMUA user CS terdaftar di sistem (role 'cs') kecuali CS utama ───
            // Tampilkan semua akun CS yang punya akun di sistem — termasuk yang belum
            // punya data pada periode ini (tampil nol). Nama mentah dari handle_by yang
            // tidak cocok dengan akun CS manapun tidak dimunculkan.
            $mainIds = $mainMembers->pluck('id')->all();

            $registeredCs = User::role('cs')
                ->where('is_active', true)
                ->with('advertiser')
                ->orderBy('nama')
                ->orderBy('email')
                ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

            foreach ($registeredCs as $cs) {
                // CS utama advertiser ini → masuk tabel utama, bukan tamu
                if (in_array($cs->id, $mainIds)) {
                    continue;
                }

                // Subtitle mengikuti penempatan pada BULAN yang sedang dilihat
                if ($useAssignments && isset($assignments[$cs->id]) && $assignments[$cs->id]->advertiser) {
                    $subtitle = 'Utama untuk '.$assignments[$cs->id]->advertiser->display_name;
                } elseif ($cs->advertiser) {
                    $subtitle = 'Utama untuk '.$cs->advertiser->display_name;
                } else {
                    $subtitle = 'CS Tamu';
                }

                $guestMembers->push((object) [
                    'id' => $cs->id, // untuk matching robust via cs_user_id
                    'panggilan' => $cs->panggilan ?: $cs->nama, // kunci lookup nama
                    'nama' => $cs->nama,
                    'display_name' => $cs->display_name,
                    'email' => $cs->email,
                    'avatar_url' => $cs->avatar_url,
                    'subtitle' => $subtitle,
                ]);
            }
        }

        // ─── Sisi advertiser: gabungkan CS Utama + CS Tamu jadi 1 daftar berurutan ──
        // Urutan: CS Utama (badge) paling atas → lalu CS lain dari porsi penerimaan
        // data (total lead) terbesar. Lead/paid dihitung dengan logika matching yang
        // sama persis dengan performa-rows (FK cs_user_id dulu, fallback nama).
        $totalOf = function ($member) use ($stats): array {
            $lead = 0;
            $paid = 0;
            foreach ($stats as $stat) {
                $isMatch = false;
                if (! empty($member->id) && ! empty($stat->cs_user_id)
                    && (int) $stat->cs_user_id === (int) $member->id) {
                    $isMatch = true;
                } elseif (strtolower(trim((string) $stat->cs_panggilan)) === strtolower(trim((string) ($member->panggilan ?? '')))
                    || strtolower(trim((string) $stat->cs_panggilan)) === strtolower(trim((string) ($member->nama ?? '')))) {
                    $isMatch = true;
                }
                if ($isMatch) {
                    $lead += (int) $stat->lead;
                    $paid += (int) $stat->paid;
                }
            }

            return [$lead, $paid];
        };

        // Tandai & hitung porsi data tiap anggota
        foreach ($mainMembers as $member) {
            $member->is_utama = true;
            [$member->total_lead, $member->total_paid] = $totalOf($member);
        }
        foreach ($guestMembers as $member) {
            $member->is_utama = false;
            [$member->total_lead, $member->total_paid] = $totalOf($member);
        }

        // Gabung: CS Utama dulu (urut porsi data antar-utama), lalu CS lain (porsi data terbesar)
        $members = $mainMembers->sortByDesc('total_lead')
            ->concat($guestMembers->sortByDesc('total_lead'))
            ->values();

        // Data diagram doughnut: porsi lead per CS yang menerima lead (termasuk CS tamu).
        // Hanya lead > 0 — CS tanpa lead tidak punya lengkungan di chart, jadi jangan
        // ikut tampil di legend (menghindari pemetaan warna yang membingungkan).
        $chartData = $members
            ->filter(fn ($m) => ($m->total_lead ?? 0) > 0)
            ->values()
            ->map(fn ($m) => [
                'label' => $m->display_name ?? $m->panggilan ?? $m->nama ?? 'CS',
                'lead' => (int) ($m->total_lead ?? 0),
                'paid' => (int) ($m->total_paid ?? 0),
                'is_utama' => (bool) ($m->is_utama ?? false),
            ])
            ->all();

        // Bangun semua tanggal dalam range
        $allDates = [];
        $start = Carbon::parse($dari);
        $end = Carbon::parse($sampai);
        while ($start->lte($end)) {
            $allDates[] = $start->format('Y-m-d');
            $start->addDay();
        }
        $today = now()->format('Y-m-d');
        $allDates = array_values(array_filter($allDates, fn ($d) => $d <= $today));

        return view('team.performance', compact(
            'byDate', 'mainMembers', 'guestMembers', 'members', 'chartData', 'totalPerCs', 'allDates',
            'dari', 'sampai', 'user',
        ));
    }

    /**
     * Daftar nomor telepon per CS — data dari Order Online Contact.
     */
    public function phoneList(Request $request): View
    {
        $user = auth()->user();

        if ($user->hasRole('cs')) {
            $advertiser = $user->advertiser;
            $advertiserId = $advertiser?->id;
            $csName = $user->panggilan ?? $user->nama;
        } else {
            $advertiserId = $user->id;
            $csName = null;
        }

        $phoneList = collect();
        if ($advertiserId) {
            $query = OrderOnlineContact::where('advertiser_id', $advertiserId);
            if ($csName) {
                $query->where('cs_name', $csName);
            }
            $phoneList = $query->orderBy('cs_name')->orderBy('phone_normalized')->get();
        }

        return view('team.phone-list', compact('phoneList', 'csName'));
    }

    /**
     * Untuk superadmin — daftar semua CS dan advertiser (opsional, kalau mau
     * lihat seluruh mapping tim). Bisa dipanggil via superadmin dashboard.
     */
    public function adminIndex(Request $request): View
    {
        abort_unless(auth()->user()->canManageAssignments(), 403);

        // Ambil semua CS beserta advertiser-nya
        $csUsers = User::role('cs')
            ->with('advertiser', 'roles')
            ->orderBy('nama')
            ->orderBy('email')
            ->paginate(20);

        // Ambil semua advertiser (untuk dropdown filter)
        $advertisers = User::role('advertiser')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'panggilan', 'email']);

        // ─── Riwayat penempatan (rotasi bulanan): matriks CS × bulan ───
        // Kolom = bulan yang punya riwayat (+ bulan berjalan), baris = semua CS.
        $bulanBerjalan = now()->format('Y-m');
        $semuaBulan = CsAssignment::query()->distinct()->orderBy('bulan')->pluck('bulan');
        if (! $semuaBulan->contains($bulanBerjalan)) {
            $semuaBulan->push($bulanBerjalan);
        }
        $semuaBulan = $semuaBulan->sort()->values()->take(-12); // maksimal 12 bulan terakhir

        $assignmentRows = CsAssignment::whereIn('bulan', $semuaBulan)
            ->with('advertiser')
            ->get(['cs_user_id', 'advertiser_id', 'bulan']);

        // Semua CS (termasuk nonaktif) agar riwayat lama tetap terlihat
        $semuaCs = User::role('cs')
            ->orderBy('nama')
            ->orderBy('email')
            ->get(['id', 'nama', 'panggilan', 'email', 'avatar', 'is_active']);

        return view('team.admin-index', compact('csUsers', 'advertisers', 'semuaBulan', 'assignmentRows', 'semuaCs', 'bulanBerjalan'));
    }

    /**
     * Board penugasan (drag & drop) CS → advertiser untuk bulan tertentu.
     * Admin pilih bulan dulu (dari halaman Mapping), lalu menyusun CS.
     */
    public function penugasan(Request $request): View
    {
        abort_unless(auth()->user()->canManageAssignments(), 403);

        $bulan = $request->input('bulan', now()->format('Y-m'));
        // Lindungi dari input bulan yang tidak valid (mencegah error Carbon di view)
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $advertisers = User::role('advertiser')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

        $csList = User::role('cs')
            ->orderBy('nama')
            ->orderBy('email')
            ->get(['id', 'nama', 'panggilan', 'email', 'avatar', 'is_active']);

        // Penempatan bulan terpilih (kosong bila bulan itu belum pernah diatur)
        $existing = CsAssignment::where('bulan', $bulan)
            ->pluck('advertiser_id', 'cs_user_id');

        return view('team.penugasan', compact('bulan', 'advertisers', 'csList', 'existing'));
    }

    /**
     * Simpan hasil drag & drop penugasan untuk bulan tertentu.
     * Bulan berjalan ikut menyinkronkan users.advertiser_id (snapshot).
     */
    public function penugasanStore(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canManageAssignments(), 403);

        $data = $request->validate([
            'bulan' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'assignments' => ['nullable', 'json'],
        ]);

        $bulan = $data['bulan'];
        $assignments = json_decode($data['assignments'] ?? '{}', true) ?: [];

        // Validasi batch: hanya CS & advertiser yang benar-benar terdaftar
        $csIds = array_keys($assignments);
        $advIds = array_values(array_filter($assignments, fn ($v) => ! empty($v)));

        $validCsIds = User::role('cs')->whereIn('id', $csIds)->pluck('id')->all();
        // Konsisten dengan board: hanya advertiser aktif yang bisa ditugaskan
        $validAdvIds = User::role('advertiser')->where('is_active', true)->whereIn('id', $advIds)->pluck('id')->all();

        DB::transaction(function () use ($bulan, $assignments, $validCsIds, $validAdvIds) {
            // Ganti total mapping bulan tersebut (manual total)
            CsAssignment::where('bulan', $bulan)->delete();

            foreach ($assignments as $csId => $advId) {
                if (! in_array((int) $csId, $validCsIds, true)) {
                    continue;
                }
                if (empty($advId) || ! in_array((int) $advId, $validAdvIds, true)) {
                    continue;
                }

                CsAssignment::create([
                    'cs_user_id' => (int) $csId,
                    'advertiser_id' => (int) $advId,
                    'bulan' => $bulan,
                    'created_by' => auth()->id(),
                ]);
            }

            // Sinkronkan snapshot users.advertiser_id hanya untuk bulan berjalan
            if ($bulan === now()->format('Y-m')) {
                User::role('cs')->update(['advertiser_id' => null]);
                // Batch update per advertiser (hindari N+1)
                foreach (CsAssignment::where('bulan', $bulan)->get()->groupBy('advertiser_id') as $advId => $rows) {
                    User::whereIn('id', $rows->pluck('cs_user_id'))->update(['advertiser_id' => $advId]);
                }
            }
        });

        $jumlah = CsAssignment::where('bulan', $bulan)->count();

        return redirect()->route('team.penugasan', ['bulan' => $bulan])
            ->with('success', "Penugasan {$bulan} berhasil disimpan ({$jumlah} CS ditugaskan).");
    }
}
