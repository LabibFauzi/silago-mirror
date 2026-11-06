<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Layanan untuk membaca dan menyimpan pengaturan sistem SILAGO.
 * Pengaturan di-cache selama 10 menit untuk performa.
 */
class SettingService
{
    private const CACHE_TTL = 600; // 10 menit
    private const CACHE_PREFIX = 'silago_setting_';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            fn () => Setting::find($key)?->value ?? $default
        );
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default ? '1' : '0');
    }

    public function all(): array
    {
        return Setting::all()->pluck('value', 'key')->toArray();
    }

    /** Hapus semua cache pengaturan (dipanggil setelah bulk update). */
    public function clearCache(): void
    {
        $keys = Setting::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }
}
