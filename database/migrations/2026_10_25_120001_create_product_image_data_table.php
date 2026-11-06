<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_image_data', function (Blueprint $table) {
            $table->foreignId('product_image_id')
                ->primary()
                ->constrained('product_images')
                ->cascadeOnDelete();
            // Base64 agar aman untuk PDO PostgreSQL/Supabase.
            $table->longText('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_data');
    }
};
