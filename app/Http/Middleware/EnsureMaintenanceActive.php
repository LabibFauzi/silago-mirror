<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan mode maintenance aktif sebelum mengizinkan akses ke fitur
 * manajemen data langsung (Data Manager). Fitur ini sangat berbahaya
 * dan hanya boleh tersedia saat aplikasi sedang dalam pemeliharaan.
 */
class EnsureMaintenanceActive
{
    public function __construct(private SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->getBool('maintenance_mode', false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Fitur ini hanya tersedia saat Mode Maintenance aktif.',
                ], 403);
            }

            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'Fitur Data Manager hanya tersedia saat Mode Maintenance aktif. Silakan aktifkan terlebih dahulu di Pengaturan.');
        }

        return $next($request);
    }
}
