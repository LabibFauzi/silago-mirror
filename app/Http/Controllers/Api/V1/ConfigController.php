<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function index()
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        
        $bannedWordsRaw = isset($settings['banned_words']) ? $settings['banned_words'] : '';
        $bannedWords = $bannedWordsRaw
            ? array_filter(array_map('trim', explode(',', strtolower($bannedWordsRaw))))
            : [];

        return response()->json([
            'max_photos' => isset($settings['max_photos']) ? (int)$settings['max_photos'] : 5,
            'maintenance_mode' => isset($settings['maintenance_mode']) ? filter_var($settings['maintenance_mode'], FILTER_VALIDATE_BOOLEAN) : false,
            'listing_active_days' => isset($settings['listing_active_days']) ? (int)$settings['listing_active_days'] : 30,
            'banned_words_enabled' => isset($settings['banned_words_enabled']) ? filter_var($settings['banned_words_enabled'], FILTER_VALIDATE_BOOLEAN) : true,
            'banned_words' => $bannedWords,
            'report_reasons' => [
                'Penipuan',
                'Barang terlarang',
                'Sengketa transaksi',
                'Perilaku pengguna',
                'Foto tidak sesuai',
                'Spam chat',
                'Lainnya'
            ],
            'allowed_email_domains' => isset($settings['allowed_email_domains']) ? $settings['allowed_email_domains'] : ''
        ]);
    }
}
