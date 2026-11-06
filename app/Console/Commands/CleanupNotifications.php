<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:purge {--days=30 : Hapus notifikasi lebih lama dari jumlah hari ini}';

    protected $description = 'Hapus notifikasi lama agar daftar notifikasi tetap ringan';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $deleted = Notification::where('created_at', '<', $threshold)->delete();

        $this->info("Menghapus {$deleted} notifikasi lebih lama dari {$days} hari.");

        return self::SUCCESS;
    }
}
