<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan pengguna adalah admin (Super Admin).
 * Dipakai untuk aksi yang hanya boleh dilakukan admin:
 * suspend pengguna, sengketa, kategori, titik COD, pengaturan, audit.
 */
class EnsureAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Hanya Super Admin yang dapat melakukan tindakan ini.'], 403);
            }
            abort(403, 'Hanya Super Admin yang dapat melakukan tindakan ini.');
        }

        return $next($request);
    }
}
