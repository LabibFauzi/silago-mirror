<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set two_factor_enabled = false untuk semua user
        DB::table('users')->update(['two_factor_enabled' => false]);

        // Update default kolom two_factor_enabled ke false (MySQL)
        DB::statement('ALTER TABLE users MODIFY two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0');

        // Set allowed_email_domains ke mhs.polines.ac.id
        DB::table('settings')->updateOrInsert(
            ['key' => 'allowed_email_domains'],
            ['value' => 'mhs.polines.ac.id']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->update(['two_factor_enabled' => true]);
        DB::statement('ALTER TABLE users MODIFY two_factor_enabled TINYINT(1) NOT NULL DEFAULT 1');
        DB::table('settings')->updateOrInsert(
            ['key' => 'allowed_email_domains'],
            ['value' => 'polines.ac.id']
        );
    }
};
