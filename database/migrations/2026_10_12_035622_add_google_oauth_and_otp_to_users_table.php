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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email_verified_at');
            $table->string('avatar')->nullable()->after('profile_photo');
            $table->string('otp_code')->nullable()->after('avatar');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->boolean('email_domain_verified')->default(false)->after('otp_expires_at');
            $table->boolean('two_factor_enabled')->default(true)->after('email_domain_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'avatar',
                'otp_code',
                'otp_expires_at',
                'email_domain_verified',
                'two_factor_enabled',
            ]);
        });
    }
};
