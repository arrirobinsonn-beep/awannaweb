<?php

namespace App\Http\Middleware;

use App\Models\MobileDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi mobile API via Bearer token.
 * Token di-hash dan dicocokkan dengan mobile_devices.token_hash.
 * Account di-resolve dari mobile device, BUKAN dari request body.
 */
class AuthMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        // Cari device berdasarkan token hash
        // Hash token plain → bandingkan dengan hash di DB
        $tokenHash = hash('sha256', $token);
        $device = MobileDevice::where('status', 'active')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Token tidak valid atau device sudah dicabut.'], 401);
        }

        // Update last_used_at
        $device->update(['last_used_at' => now()]);

        // Bind device & account ke request
        $request->attributes->set('mobile_device', $device);
        $request->attributes->set('mobile_account', $device->account);

        return $next($request);
    }
}
