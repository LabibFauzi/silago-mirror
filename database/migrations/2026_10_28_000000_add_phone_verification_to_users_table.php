<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferensi tampilkan nomor WhatsApp di profil & chat.
     * Nomor sendiri cukup diisi langsung (tanpa OTP).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'show_phone')) {
                $table->boolean('show_phone')->default(false)->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'show_phone')) {
                $table->dropColumn('show_phone');
            }
        });
    }
};
