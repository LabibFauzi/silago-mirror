<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set two_factor_enabled = false untuk semua user
        DB::table('users')->update(['two_factor_enabled' => false]);

        // Update default kolom two_factor_enabled ke false (DB-agnostic)
        // Cara: drop kolom lalu re-add dengan default 0 (false).
        // Karena di migrasi sebelumnya kolomnya NOT NULL DEFAULT, kita ubah defaultnya.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            // PostgreSQL: set default via ALTER COLUMN ... SET DEFAULT
            DB::statement('ALTER TABLE users ALTER COLUMN two_factor_enabled SET DEFAULT false');
        } else {
            // MySQL / MariaDB
            DB::statement('ALTER TABLE users MODIFY two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0');
        }

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

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN two_factor_enabled SET DEFAULT true');
        } else {
            DB::statement('ALTER TABLE users MODIFY two_factor_enabled TINYINT(1) NOT NULL DEFAULT 1');
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'allowed_email_domains'],
            ['value' => 'polines.ac.id']
        );
    }
};
