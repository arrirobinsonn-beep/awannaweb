<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // ─── Complete Profile (first-login flow) ───────────────────

    public function showCompleteProfile(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user->is_profile_complete) {
            return redirect()->route('dashboard');
        }

        return view('auth.complete-profile', compact('user'));
    }

    public function storeCompleteProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'panggilan' => ['required', 'string', 'max:60'],
            'role' => ['required', 'string', 'max:60'],
            'nohp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:500'],
            'bank' => ['required', 'string', 'max:60'],
            'norek' => ['required', 'string', 'max:30'],
        ]);

        $data['is_profile_complete'] = true;
        $user->update($data);

        return redirect()->route('dashboard')
            ->with('success', 'Profil berhasil dilengkapi. Selamat datang, '.$user->panggilan.'!');
    }

    // ─── Profile Page (authenticated) ──────────────────────────

    /** Tampilkan halaman profil user */
    public function show(): View
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    /** Update data pribadi */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'panggilan' => ['required', 'string', 'max:60'],
            'nohp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:500'],
            'bank' => ['required', 'string', 'max:60'],
            'norek' => ['required', 'string', 'max:30'],
        ]);

        $user->update($data);

        return redirect()->route('profile.show')
            ->with('success', 'Data profil berhasil diperbarui.');
    }

    /** Ganti password */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required'],
            'password_baru' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak sesuai.'])
                ->with('tab', 'password');
        }

        $user->update(['password' => Hash::make($request->password_baru)]);

        return redirect()->route('profile.show')
            ->with('success', 'Password berhasil diubah. Silakan login ulang jika diperlukan.')
            ->with('tab', 'password');
    }
}
