<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Gambar baru disimpan di tabel product_image_data. Kolom ini
            // dipertahankan untuk kompatibilitas gambar lama yang masih di disk.
            // Catatan: tidak gunakan ->change() karena PostgreSQL tidak support.
            // Gambar baru akan set image_path ke empty string ''.
            $table->boolean('has_data')->default(false)->after('image_path');
            $table->string('mime_type')->nullable()->after('has_data');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['has_data', 'mime_type']);
        });
    }
};
