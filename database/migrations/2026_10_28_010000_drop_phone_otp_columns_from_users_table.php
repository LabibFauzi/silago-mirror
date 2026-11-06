<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur nomor WhatsApp tidak lagi memakai OTP, jadi kolom verifikasi dihapus.
 * Nomor cukup diisi langsung oleh pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'pending_phone',
            'phone_otp',
            'phone_otp_expires_at',
            'phone_otp_sent_at',
            'phone_otp_attempts',
            'phone_verified_at',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn('users', $column),
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan; fitur OTP sudah dihapus.
    }
};
