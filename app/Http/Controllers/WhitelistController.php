<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhitelistController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isAdvertiser = $user->hasRole('advertiser');

        $query = Whitelist::with('user')->latest();

        // Advertiser hanya lihat whitelist miliknya
        if ($isAdvertiser) {
            $query->where('user_id', $user->id);
        }

        // ── Sisi admin/superadmin: tab per advertiser pemilik, default "Semua whitelist" ──
        $advertisers = collect();
        $activeTab = 'all';
        $countPerAdv = [];
        $allCount = 0;

        if (! $isAdvertiser) {
            $advertisers = User::role('advertiser')
                ->orderBy('nama')
                ->get(['id', 'nama', 'panggilan', 'email', 'avatar']);

            $activeTab = (string) $request->input('tab', 'all');

            if ($activeTab !== 'all') {
                $query->where('user_id', $activeTab);
            }
        }

        // Filter search/platform/status — dipakai juga utk hitungan badge tab
        $applyFilters = function ($q) use ($request) {
            if ($request->filled('search')) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('nama', 'like', '%'.$request->search.'%')
                        ->orWhere('kode', 'like', '%'.$request->search.'%');
                });
            }

            if ($request->filled('platform')) {
                $q->where('platform', $request->platform);
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
        };

        $applyFilters($query);

        // Hitungan per advertiser + total "Semua whitelist" (1 query aggregate, anti N+1)
        if (! $isAdvertiser && $advertisers->isNotEmpty()) {
            $countQuery = Whitelist::query();
            $applyFilters($countQuery);

            $countPerAdv = (clone $countQuery)
                ->whereIn('user_id', $advertisers->pluck('id'))
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id')
                ->map(fn ($n) => (int) $n)
                ->all();

            $allCount = array_sum($countPerAdv);
        }

        $whitelists = $query->paginate(15)->withQueryString();
        $platforms = Whitelist::distinct()->pluck('platform')->filter()->sort()->values();

        return view('whitelist.index', compact('whitelists', 'platforms', 'advertisers', 'activeTab', 'countPerAdv', 'allCount'));
    }

    /**
     * Pengelolaan whitelist (buat/ubah/hapus) HANYA untuk role advertiser.
     * Admin/superadmin/owner hanya bisa melihat (whitelist.view).
     */
    private function abortUnlessAdvertiser(): void
    {
        abort_unless(auth()->user()->hasRole('advertiser'), 403, 'Hanya role advertiser yang dapat mengelola whitelist.');
    }

    public function create(): View
    {
        $this->abortUnlessAdvertiser();

        return view('whitelist.form', [
            'whitelist' => new Whitelist,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortUnlessAdvertiser();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:50', 'unique:whitelists,kode'],
            'platform' => ['required', 'string', 'max:50'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'catatan' => ['nullable', 'string'],
            'total_topup' => ['nullable', 'numeric', 'min:0'],
            'nominal_terakhir_topup' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Kepemilikan otomatis = advertiser yang sedang login
        $data['user_id'] = auth()->id();

        // Nominal top up awal boleh diisi sekalian
        $data['total_topup'] = $request->input('total_topup', 0);
        $data['nominal_terakhir_topup'] = $request->input('nominal_terakhir_topup', 0);
        $data['total_spending'] = 0;

        Whitelist::create($data);

        return redirect()->route('whitelist.index')
            ->with('success', 'Whitelist berhasil ditambahkan.');
    }

    public function show(Whitelist $whitelist): View
    {
        $whitelist->load('user', 'spendingHarians.user');

        return view('whitelist.show', compact('whitelist'));
    }

    public function edit(Whitelist $whitelist): View
    {
        $this->abortUnlessAdvertiser();

        return view('whitelist.form', [
            'whitelist' => $whitelist,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Whitelist $whitelist): RedirectResponse
    {
        $this->abortUnlessAdvertiser();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:50', 'unique:whitelists,kode,'.$whitelist->id],
            'platform' => ['required', 'string', 'max:50'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'total_topup' => ['nullable', 'numeric', 'min:0'],
            'nominal_terakhir_topup' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        // user_id tidak ikut diubah — kepemilikan tetap milik pemilik awal

        $whitelist->update($data);

        return redirect()->route('whitelist.index')
            ->with('success', 'Whitelist berhasil diperbarui.');
    }

    public function destroy(Whitelist $whitelist): RedirectResponse
    {
        $this->abortUnlessAdvertiser();

        $whitelist->delete();

        return redirect()->route('whitelist.index')
            ->with('success', 'Whitelist berhasil dihapus.');
    }
}
