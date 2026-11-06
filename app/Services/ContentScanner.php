<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * ContentScanner: mencocokkan nama dan deskripsi barang dengan daftar
 * kata terlarang dari pengaturan sistem. Mengisi scan_result dan scan_note.
 *
 * Nilai scan_result: safe, flagged
 */
class ContentScanner
{
    /**
     * Pindai teks dan kembalikan [scan_result, scan_note].
     */
    public function scan(string $name, ?string $description = null): array
    {
        // Cek apakah filter aktif
        $enabled = (bool) Setting::find('banned_words_enabled')?->value;
        if (! $enabled) {
            return ['safe', null];
        }

        $bannedWordsRaw = Setting::find('banned_words')?->value ?? '';
        // Support separator koma atau newline
        $words = array_filter(
            array_map('trim', preg_split('/[\n,]/', strtolower($bannedWordsRaw)))
        );

        if (empty($words)) {
            return ['safe', null];
        }

        $text = strtolower($name . ' ' . ($description ?? ''));
        $found = [];

        foreach ($words as $word) {
            if ($word !== '' && str_contains($text, $word)) {
                $found[] = $word;
            }
        }

        if (empty($found)) {
            return ['safe', null];
        }

        return ['flagged', implode(', ', array_slice($found, 0, 5))];
    }
}
