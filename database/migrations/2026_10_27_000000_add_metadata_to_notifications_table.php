<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('data')->nullable()->after('reference_id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        // Baris lama tetap bisa diurutkan berdasarkan waktu aktivitas terakhir.
        DB::table('notifications')
            ->whereNull('updated_at')
            ->update(['updated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['data', 'updated_at']);
        });
    }
};
