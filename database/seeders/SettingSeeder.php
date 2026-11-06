<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Mengisi tabel settings dengan nilai awal sesuai database.md bagian 8.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name',                 'value' => 'SILAGO'],
            ['key' => 'support_email',             'value' => 'support@silago.id'],
            ['key' => 'max_photos',                'value' => '5'],
            ['key' => 'listing_active_days',       'value' => '30'],
            ['key' => 'session_timeout_minutes',   'value' => '30'],
            ['key' => 'admin_2fa_required',        'value' => '0'],
            ['key' => 'maintenance_mode',          'value' => '0'],
            ['key' => 'maintenance_starts_at',     'value' => ''],
            ['key' => 'maintenance_ends_at',       'value' => ''],
            ['key' => 'maintenance_progress',      'value' => '0'],
            ['key' => 'banned_words_enabled',      'value' => '1'],
            ['key' => 'banned_words',              'value' => "vape\njudi\nnarkoba\nporno\nbokep\nsara\nktp\nijazah palsu\njoki tugas"],
            ['key' => 'allowed_email_domains',     'value' => ''],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }
    }
}
