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
