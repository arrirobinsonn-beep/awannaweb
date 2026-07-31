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
        $query = Whitelist::with('user')->latest();

        // Advertiser hanya lihat whitelist miliknya
        if ($user->hasRole('advertiser')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->search.'%')
                    ->orWhere('kode', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $whitelists = $query->paginate(15)->withQueryString();
        $platforms = Whitelist::distinct()->pluck('platform')->filter()->sort()->values();

        return view('whitelist.index', compact('whitelists', 'platforms'));
    }

    public function create(): View
    {
        // Daftar advertiser untuk pilihan pemilik
        $advertisers = User::role('advertiser')
            ->where('is_active', true)
            ->get(['id', 'nama', 'panggilan', 'email']);

        return view('whitelist.form', [
            'whitelist' => new Whitelist,
            'advertisers' => $advertisers,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:50', 'unique:whitelists,kode'],
            'platform' => ['required', 'string', 'max:50'],
            'user_id' => ['required', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'catatan' => ['nullable', 'string'],
            'total_topup' => ['nullable', 'numeric', 'min:0'],
            'nominal_terakhir_topup' => ['nullable', 'numeric', 'min:0'],
        ]);

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
        $advertisers = User::role('advertiser')
            ->where('is_active', true)
            ->get(['id', 'nama', 'panggilan', 'email']);

        return view('whitelist.form', [
            'whitelist' => $whitelist,
            'advertisers' => $advertisers,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Whitelist $whitelist): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:50', 'unique:whitelists,kode,'.$whitelist->id],
            'platform' => ['required', 'string', 'max:50'],
            'user_id' => ['required', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'total_topup' => ['nullable', 'numeric', 'min:0'],
            'nominal_terakhir_topup' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        $whitelist->update($data);

        return redirect()->route('whitelist.index')
            ->with('success', 'Whitelist berhasil diperbarui.');
    }

    public function destroy(Whitelist $whitelist): RedirectResponse
    {
        $whitelist->delete();

        return redirect()->route('whitelist.index')
            ->with('success', 'Whitelist berhasil dihapus.');
    }
}
