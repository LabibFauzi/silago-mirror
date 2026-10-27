<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('message');
            $table->string('image_thumbnail')->nullable()->after('image_path');
            $table->text('image_caption')->nullable()->after('image_thumbnail');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->enum('offer_status', ['pending', 'accepted', 'rejected', 'countered', 'withdrawn'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_thumbnail', 'image_caption']);
            $table->string('offer_status')->nullable()->change();
        });
    }
};
