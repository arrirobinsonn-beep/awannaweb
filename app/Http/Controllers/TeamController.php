<?php

namespace App\Http\Controllers;

use App\Models\OrderOnlineContact;
use App\Models\RegionalCsStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        // Tampilkan SEMUA CS: CS Utama (yang dikhususkan untuk advertiser ini)
        // dan CS Tamu (seluruh CS lain yang masuk rotasi bulanan).
        $allCs = User::role('cs')
            ->with('roles', 'advertiser')
            ->orderBy('nama')
            ->orderBy('email')
            ->get();

        $mainCs = $allCs->where('advertiser_id', $user->id)->values();
        $guestCs = $allCs->where('advertiser_id', '!=', $user->id)->values();

        return view('team.index', compact('mainCs', 'guestCs', 'user'));
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

        // Tentukan advertiser pemilik data (advertiser → dirinya; CS → atasan)
        $advertiserId = $user->hasRole('advertiser') ? $user->id : $user->advertiser_id;

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

            // CS Utama = CS yang dikhususkan untuk advertiser ini
            $mainMembers = User::where('advertiser_id', $advertiserId)
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

            // Total per CS (untuk kartu statistik)
            foreach ($stats->groupBy('cs_panggilan') as $csName => $csStats) {
                $totalPerCs[$csName] = [
                    'lead' => $csStats->sum('lead'),
                    'paid' => $csStats->sum('paid'),
                ];
            }

            // ─── CS Tamu: cs_panggilan yang menangani order tapi bukan CS utama ───
            $mainPanggilan = $mainMembers->pluck('panggilan')->filter()
                ->map(fn ($p) => strtolower(trim($p)))->all();
            $mainNama = $mainMembers->pluck('nama')->filter()
                ->map(fn ($n) => strtolower(trim($n)))->all();
            $mainKeys = array_merge($mainPanggilan, $mainNama);

            $guestNames = $stats->pluck('cs_panggilan')->filter()
                ->map(fn ($n) => strtolower(trim($n)))
                ->unique()
                ->reject(fn ($n) => in_array($n, $mainKeys))
                ->values();

            foreach ($guestNames as $gName) {
                $stat = $stats->first(fn ($s) => strtolower(trim((string) $s->cs_panggilan)) === $gName);
                $csUser = $stat?->csUser;
                $rawName = $stat?->cs_panggilan ?: $gName;

                $guestMembers->push((object) [
                    'panggilan' => $rawName,
                    'nama' => $csUser?->nama,
                    'display_name' => $csUser ? $csUser->display_name : $rawName,
                    'email' => $csUser?->email,
                    'avatar_url' => $csUser
                        ? $csUser->avatar_url
                        : 'https://ui-avatars.com/api/?name=' . urlencode($rawName) . '&background=6B7280&color=fff&bold=true',
                    'subtitle' => $csUser?->advertiser
                        ? 'Utama untuk ' . $csUser->advertiser->display_name
                        : 'CS Tamu',
                ]);
            }
        }

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
            'byDate', 'mainMembers', 'guestMembers', 'totalPerCs', 'allDates',
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
        abort_unless(auth()->user()->canCreateUser(), 403);

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

        return view('team.admin-index', compact('csUsers', 'advertisers'));
    }
}