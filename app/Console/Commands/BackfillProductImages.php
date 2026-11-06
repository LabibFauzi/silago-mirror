<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillProductImages extends Command
{
    protected $signature = 'images:backfill {--chunk=100}';

    protected $description = 'Salin gambar produk lama dari disk ke database (PostgreSQL/Supabase)';

    public function handle(): int
    {
        $copied = 0;
        $missing = 0;

        ProductImage::query()
            ->where('has_data', false)
            ->whereNotNull('image_path')
            ->where(function ($query) {
                $query->where('image_path', 'not like', 'http://%')
                    ->where('image_path', 'not like', 'https://%');
            })
            ->chunkById((int) $this->option('chunk'), function ($images) use (&$copied, &$missing) {
                foreach ($images as $image) {
                    $path = ltrim($image->image_path, '/');
                    $fullPath = storage_path('app/public/' . $path);

                    if (! is_file($fullPath)) {
                        $missing++;
                        continue;
                    }

                    $contents = file_get_contents($fullPath);
                    if ($contents === false) {
                        $missing++;
                        continue;
                    }

                    DB::transaction(function () use ($image, $contents, $fullPath) {
                        $image->update([
                            'has_data' => true,
                            'mime_type' => mime_content_type($fullPath) ?: 'image/jpeg',
                        ]);

                        $image->data()->delete();
                        $image->data()->create([
                            'data' => base64_encode($contents),
                        ]);
                    });

                    $copied++;
                }
            });

        $this->info("Selesai. Tersalin: {$copied}, file tidak ditemukan: {$missing}.");

        return self::SUCCESS;
    }
}
