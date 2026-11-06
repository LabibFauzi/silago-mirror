<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Membuat akun admin dan moderator awal.
 * PASSWORD WAJIB DIGANTI SEBELUM DEPLOY!
 *
 * Akun staf TIDAK dibuat lewat pendaftaran publik (rules A10).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@silago.id'],
            [
                'name'              => 'Admin SILAGO',
                'username'          => 'superadmin',
                'email_verified_at' => now(),
                'password'          => Hash::make('admin12345!'),
            ]
        )->forceFill(['role' => 'admin', 'status' => 'active'])->saveQuietly();

        // Moderator 1
        User::updateOrCreate(
            ['email' => 'moderator1@silago.id'],
            [
                'name'              => 'Moderator Satu',
                'username'          => 'moderator1',
                'email_verified_at' => now(),
                'password'          => Hash::make('mod12345!'),
            ]
        )->forceFill(['role' => 'moderator', 'status' => 'active'])->saveQuietly();

        // Moderator 2
        User::updateOrCreate(
            ['email' => 'moderator2@silago.id'],
            [
                'name'              => 'Moderator Dua',
                'username'          => 'moderator2',
                'email_verified_at' => now(),
                'password'          => Hash::make('mod12345!'),
            ]
        )->forceFill(['role' => 'moderator', 'status' => 'active'])->saveQuietly();

        $this->command->warn('⚠️  Ganti password akun admin dan moderator sebelum deploy!');
        $this->command->info('Admin: admin@silago.id / admin12345!');
        $this->command->info('Mod 1: moderator1@silago.id / mod12345!');
    }
}
