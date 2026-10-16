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
                if ($request->has('form_section')) {
                    $section = $request->input('form_section');
                    if ($section === 'general') {
                        foreach (['app_name', 'support_email', 'max_photos', 'listing_active_days'] as $k) {
                            if ($request->has($k)) {
                                $this->settingService->set($k, $request->input($k));
                            }
                        }
                    } elseif ($section === 'moderation') {
                        $this->settingService->set('banned_words_enabled', $request->has('banned_words_enabled') ? '1' : '0');
                        if ($request->has('banned_words')) {
                            $this->settingService->set('banned_words', $request->input('banned_words'));
                        }
                    } elseif ($section === 'security') {
                        if ($request->has('session_timeout_minutes')) {
                            $this->settingService->set('session_timeout_minutes', $request->input('session_timeout_minutes'));
                        }
                    } elseif ($section === 'system') {
                        $this->settingService->set('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0');
                    }
                } else {
                    $keys = [
                        'app_name', 'support_email', 'max_photos', 'listing_active_days',
                        'session_timeout_minutes', 'banned_words'
                    ];

                    foreach ($keys as $key) {
                        if ($request->has($key)) {
                            $this->settingService->set($key, $request->input($key));
                        }
                    }

                    if ($request->has('has_checkboxes')) {
                        foreach (['maintenance_mode', 'banned_words_enabled'] as $checkbox) {
                            $this->settingService->set($checkbox, $request->has($checkbox) ? '1' : '0');
                        }
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
