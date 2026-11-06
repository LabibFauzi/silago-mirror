<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akses dari pengguna berstatus suspended.
 * Juga mencabut token jika akun disuspend (rules A11).
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'suspended') {
            // Cabut token yang sedang dipakai
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Akun kamu telah ditangguhkan. Hubungi dukungan untuk informasi lebih lanjut.',
            ], 401);
        }

        return $next($request);
    }
}
