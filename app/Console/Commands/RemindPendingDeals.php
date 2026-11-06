<?php

namespace App\Console\Commands;

use App\Models\MarketplaceDeal;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mengingatkan pembeli yang belum memindai QR.
 * Dijalankan harian via scheduler.
 */
class RemindPendingDeals extends Command
{
    protected $signature   = 'deals:remind-pending';
    protected $description = 'Ingatkan pembeli yang belum memindai QR penyelesaian';

    public function __construct(private NotificationService $notif)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Ingatkan deal agreed yang sudah lebih dari 24 jam belum selesai
        $count = 0;

        MarketplaceDeal::query()
            ->where('status', 'agreed')
            ->where('agreed_at', '<=', now()->subDay())
            ->with(['buyer', 'seller', 'product'])
            ->chunk(50, function ($deals) use (&$count) {
                foreach ($deals as $deal) {
                    try {
                        $this->notif->send(
                            $deal->buyer_id,
                            'Pengingat Transaksi',
                            "Kamu masih punya kesepakatan yang belum diselesaikan untuk barang \"{$deal->product->name}\".",
                            'reminder_deal',
                            $deal->id
                        );
                        $count++;
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim pengingat deal', [
                            'deal_id' => $deal->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Berhasil mengirim {$count} pengingat transaksi.");

        return self::SUCCESS;
    }
}
