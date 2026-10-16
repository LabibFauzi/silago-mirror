<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya izinkan akses CRUD saat maintenance mode aktif.
 * Untuk keamanan data, operasi CRUD tabel hanya boleh dilakukan saat sistem maintenance.
 */
class CheckMaintenanceForCrud
{
    public function __construct(private SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isActive = $this->settings->getBool('maintenance_mode', false);

        if (!$isActive) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CRUD tabel hanya tersedia saat mode maintenance aktif.',
                ], 403);
            }
            return redirect()->route('admin.dashboard')
                ->with('error', 'CRUD tabel hanya tersedia saat mode maintenance aktif.');
        }

        return $next($request);
    }
}
