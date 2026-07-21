<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Jika user sudah login tapi profil belum lengkap,
     * paksa redirect ke halaman complete-profile.
     *
     * Kecualikan route: complete-profile, logout, dan asset.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Daftar route yang boleh diakses meski profil belum lengkap
        $allowed = [
            'profile.complete',
            'profile.complete.store',
            'logout',
        ];

        if (
            ! $user->is_profile_complete &&
            ! $request->routeIs($allowed)
        ) {
            return redirect()->route('profile.complete')
                ->with('info', 'Lengkapi profil Anda terlebih dahulu sebelum mengakses sistem.');
        }

        return $next($request);
    }
}
