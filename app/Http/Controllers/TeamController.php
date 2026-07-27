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

        // Ambil semua CS yang terasosiasi dengan advertiser ini
        $team = User::where('advertiser_id', $user->id)
            ->with('roles')
            ->orderBy('nama')
            ->orderBy('email')
            ->get();

        return view('team.index', compact('team', 'user'));
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

        if ($user->hasRole('cs')) {
            // CS lihat performa tim — data dari advertiser tempat bernaung
            $advertiser = $user->advertiser;
            $advertiserId = $advertiser?->id;

            $stats = collect();
            $teamMembers = collect();
            $totalPerCs = [];
            $byDate = [];

            if ($advertiserId) {
                $stats = RegionalCsStat::where('user_id', $advertiserId)
                    ->with('csUser')
                    ->whereBetween('tanggal', [$dari, $sampai])
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('cs_panggilan', 'asc')
                    ->get();

                foreach ($stats as $stat) {
                    $tglKey = $stat->tanggal instanceof Carbon
                        ? $stat->tanggal->format('Y-m-d')
                        : substr((string) $stat->tanggal, 0, 10);
                    if (! isset($byDate[$tglKey])) {
                        $byDate[$tglKey] = [];
                    }
                    $byDate[$tglKey][] = $stat;
                }

                $teamMembers = User::where('advertiser_id', $advertiserId)
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

                foreach ($stats->groupBy('cs_panggilan') as $csName => $csStats) {
                    $totalPerCs[$csName] = [
                        'lead' => $csStats->sum('lead'),
                        'paid' => $csStats->sum('paid'),
                    ];
                }
            }

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
                'byDate', 'teamMembers', 'totalPerCs', 'allDates',
                'dari', 'sampai', 'user',
            ));
        }

        abort_unless($user->hasRole('advertiser'), 403, 'Halaman ini hanya untuk Advertiser.');

        // Ambil semua CS stats milik advertiser ini di range tanggal
        $stats = RegionalCsStat::where('user_id', $user->id)
            ->with('csUser')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal', 'asc')
            ->orderBy('cs_panggilan', 'asc')
            ->get();

        // Group by tanggal → CS
        $byDate = [];
        foreach ($stats as $stat) {
            $tglKey = $stat->tanggal instanceof Carbon
                ? $stat->tanggal->format('Y-m-d')
                : substr((string) $stat->tanggal, 0, 10);

            if (! isset($byDate[$tglKey])) {
                $byDate[$tglKey] = [];
            }
            $byDate[$tglKey][] = $stat;
        }

        // Ambil semua CS yang menjadi tim advertiser ini (termasuk yang mungkin belum punya data)
        $teamMembers = User::where('advertiser_id', $user->id)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

        // Hitung total per CS
        $totalPerCs = [];
        foreach ($stats->groupBy('cs_panggilan') as $csName => $csStats) {
            $totalPerCs[$csName] = [
                'lead' => $csStats->sum('lead'),
                'paid' => $csStats->sum('paid'),
            ];
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
            'byDate',
            'teamMembers',
            'totalPerCs',
            'allDates',
            'dari',
            'sampai',
            'user',
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