<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin(Auth::user());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ─── Helper ────────────────────────────────────────────────

    /**
     * Tentukan ke mana redirect setelah login:
     * - Jika profil belum lengkap → paksa ke halaman complete-profile
     * - Jika sudah lengkap → ke dashboard (atau intended URL)
     */
    private function redirectAfterLogin($user): RedirectResponse
    {
        if (! $user->is_profile_complete) {
            return redirect()->route('profile.complete')
                ->with('info', 'Selamat datang! Silakan lengkapi profil Anda terlebih dahulu.');
        }

        return redirect()->intended(route('dashboard'));
    }
}
