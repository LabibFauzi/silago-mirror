<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom gambar ke messages.
     *
     * Migrasi ini dibuat idempotent karena deployment pertama di PostgreSQL
     * sempat gagal saat mengubah enum offer_status. Jika kolom gambar sudah
     * terlanjur dibuat, deployment berikutnya tidak boleh gagal lagi.
     */
    public function up(): void
    {
        $hasImagePath = Schema::hasColumn('messages', 'image_path');
        $hasImageThumbnail = Schema::hasColumn('messages', 'image_thumbnail');
        $hasImageCaption = Schema::hasColumn('messages', 'image_caption');

        if (! $hasImagePath || ! $hasImageThumbnail || ! $hasImageCaption) {
            Schema::table('messages', function (Blueprint $table) use (
                $hasImagePath,
                $hasImageThumbnail,
                $hasImageCaption,
            ) {
                if (! $hasImagePath) {
                    $table->string('image_path')->nullable()->after('message');
                }

                if (! $hasImageThumbnail) {
                    $table->string('image_thumbnail')->nullable()->after('image_path');
                }

                if (! $hasImageCaption) {
                    $table->text('image_caption')->nullable()->after('image_thumbnail');
                }
            });
        }

        // Di PostgreSQL, Laravel menghasilkan SQL enum yang tidak valid
        // (`varchar ... check (...)`) saat memakai change(). Kolom aslinya
        // sudah berupa varchar, jadi validasi nilai cukup dilakukan aplikasi.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('messages', function (Blueprint $table) {
                $table->enum('offer_status', [
                    'pending',
                    'accepted',
                    'rejected',
                    'countered',
                    'withdrawn',
                ])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(
            ['image_path', 'image_thumbnail', 'image_caption'],
            fn (string $column) => Schema::hasColumn('messages', $column),
        ));

        if ($columns !== []) {
            Schema::table('messages', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('offer_status')->nullable()->change();
            });
        }
    }
};
