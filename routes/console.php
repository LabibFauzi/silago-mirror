<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Scheduler - SILAGO
|--------------------------------------------------------------------------
*/

// Arsipkan barang kadaluarsa setiap hari jam 01:00 WIB
Schedule::command('products:archive-expired')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// Ingatkan pembeli yang belum memindai QR setiap hari jam 09:00 WIB
Schedule::command('deals:remind-pending')
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// Ingatkan pembeli yang belum mengulas transaksi selesai setiap hari jam 10:00 WIB
Schedule::command('deals:remind-reviews')
    ->dailyAt('10:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// Bersihkan notifikasi lama (default 30 hari).
Schedule::command('notifications:purge')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// Hapus chat biasa tanpa offer/deal yang tidak aktif lebih dari 24 jam.
// Dijalankan tiap jam agar chat sementara cepat dibersihkan.
Schedule::command('chat:purge')
    ->hourly()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();
