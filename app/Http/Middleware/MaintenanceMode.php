<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memblokir semua request API saat mode maintenance aktif.
 * Mengembalikan 503 dengan informasi waktu pemeliharaan.
 */
class MaintenanceMode
{
    public function __construct(private SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isActive = $this->settings->getBool('maintenance_mode', false);

        if ($isActive) {
            return response()->json([
                'message'   => 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi nanti.',
                'starts_at' => $this->settings->get('maintenance_starts_at'),
                'ends_at'   => $this->settings->get('maintenance_ends_at'),
            ], 503);
        }

        return $next($request);
    }
}
