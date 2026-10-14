<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SettingService $settingService
    ) {}

    public function index(): View
    {
        $settings = $this->settingService->all();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'max_photos' => ['nullable', 'integer', 'min:1'],
            'listing_active_days' => ['nullable', 'integer', 'min:1'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:1'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'banned_words_enabled' => ['nullable', 'boolean'],
            'banned_words' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $keys = [
                    'app_name', 'support_email', 'max_photos', 'listing_active_days',
                    'session_timeout_minutes', 'maintenance_mode',
                    'banned_words_enabled', 'banned_words'
                ];

                foreach ($keys as $key) {
                    if ($request->has($key)) {
                        $this->settingService->set($key, $request->input($key));
                    }
                }

                // Handle unchecked checkboxes (browsers don't send them)
                foreach (['maintenance_mode', 'banned_words_enabled'] as $checkbox) {
                    if (!$request->has($checkbox)) {
                        $this->settingService->set($checkbox, '0');
                    }
                }

                if (method_exists($this->settingService, 'clearCache')) {
                    $this->settingService->clearCache();
                }

                $this->auditLogService->logSetting('Ubah pengaturan', 'Pengaturan sistem');
            });

            return back()->with('success', 'Pengaturan berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }
}
