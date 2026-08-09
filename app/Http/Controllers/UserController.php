<?php

namespace App\Http\Controllers;

use App\Models\CsAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('panggilan', 'like', '%'.$request->search.'%')
                    ->orWhere('role', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('user.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        // Hanya owner & super_admin boleh membuat akun baru
        abort_unless(auth()->user()->canCreateUser(), 403, 'Hanya Owner atau Super Admin yang dapat menambahkan user.');

        $roles = Role::whereNotIn('name', ['owner'])->get(); // owner tidak bisa dibuat via UI

        return view('user.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canCreateUser(), 403);

        // Form create hanya butuh email, password, dan role
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'is_profile_complete' => false,  // wajib false saat dibuat
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('user.index')
            ->with('success', "Akun untuk {$data['email']} berhasil dibuat. User harus melengkapi profil saat login pertama.");
    }

    public function show(User $user): View
    {
        $user->load('roles', 'spendingHarians');

        return view('user.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = Role::all();

        // Untuk superadmin: daftar advertiser untuk assignment CS
        $advertisers = collect();
        if (auth()->user()->canCreateUser()) {
            $advertisers = User::role('advertiser')
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'panggilan', 'email']);
        }

        // CS user yang bisa dijadikan tim (untuk dropdown advertiser edit)
        $csUsers = collect();
        if (auth()->user()->canCreateUser()) {
            $csUsers = User::role('cs')
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'panggilan', 'email']);
        }

        // Riwayat penempatan CS (rotasi bulanan)
        $csAssignments = collect();
        if (auth()->user()->canCreateUser() && $user->hasRole('cs')) {
            $csAssignments = CsAssignment::where('cs_user_id', $user->id)
                ->with('advertiser')
                ->orderByDesc('bulan')
                ->get();
        }

        return view('user.edit', compact('user', 'roles', 'advertisers', 'csUsers', 'csAssignments'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Owner & super_admin bisa edit semua field
        // User biasa hanya bisa edit profilnya sendiri (ditangani ProfileController)
        abort_unless(
            auth()->user()->canCreateUser() || auth()->id() === $user->id,
            403
        );

        $rules = [
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role' => ['nullable', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ];

        // Hanya superadmin yang bisa set advertiser_id & penempatan bulanan
        if (auth()->user()->canCreateUser()) {
            $rules['advertiser_id'] = ['nullable', 'exists:users,id'];
            $rules['bulan'] = ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'];
        }

        $data = $request->validate($rules);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        // Apakah user (setelah role diubah) tetap ber-role CS?
        $isCs = $user->hasRole('cs') || ($data['role'] ?? null) === 'cs';

        if (! empty($data['role']) && $data['role'] !== 'cs') {
            // Role berpindah dari CS → bersihkan snapshot & riwayat penempatan
            $data['advertiser_id'] = null;
            CsAssignment::where('cs_user_id', $user->id)->delete();
        } elseif (! $isCs) {
            // Bukan CS → tidak ada penempatan CS utama
            $data['advertiser_id'] = null;
        } elseif (auth()->user()->canCreateUser() && $request->filled('bulan')) {
            // ─── Penempatan CS Utama per bulan (rotasi bulanan) ─────────
            // Admin mengatur 'CS Utama untuk' per bulan. Bulan berjalan ikut
            // menyinkronkan users.advertiser_id (snapshot) supaya fitur lama tetap jalan.
            $bulan = $request->input('bulan');
            $advertiserId = $data['advertiser_id'] ?? null;

            if ($advertiserId) {
                CsAssignment::updateOrCreate(
                    ['cs_user_id' => $user->id, 'bulan' => $bulan],
                    ['advertiser_id' => $advertiserId, 'created_by' => auth()->id()]
                );
            } else {
                // Kosongkan penempatan → hapus riwayat bulan tersebut
                CsAssignment::where('cs_user_id', $user->id)->where('bulan', $bulan)->delete();
            }

            if ($bulan === now()->format('Y-m')) {
                // Snapshot hanya mengikuti bulan berjalan
                $data['advertiser_id'] = $advertiserId;
            } else {
                // Edit bulan lain tidak boleh mengubah penunjuk bulan berjalan
                unset($data['advertiser_id']);
            }
        }
        // Catatan: CS dengan request non-standar (tanpa 'bulan') → snapshot & riwayat dibiarkan apa adanya

        $user->update($data);

        if (! empty($data['role']) && auth()->user()->canCreateUser()) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('user.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->canCreateUser(), 403);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return redirect()->route('user.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /** Toggle aktif/nonaktif user */
    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->canCreateUser(), 403);

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User {$user->display_name} berhasil {$status}.");
    }
}
