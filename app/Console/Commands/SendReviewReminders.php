<?php

namespace App\Console\Commands;

use App\Models\MarketplaceDeal;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mengingatkan pembeli yang belum mengulas transaksi yang selesai.
 * Dijalankan harian via scheduler.
 */
class SendReviewReminders extends Command
{
    protected $signature = 'deals:remind-reviews';

    protected $description = 'Ingatkan pembeli yang belum memberi ulasan setelah transaksi selesai';

    public function __construct(private NotificationService $notif)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = 0;

        MarketplaceDeal::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', now()->subDay())
            ->whereDoesntHave('reviews', function ($query) {
                $query->whereColumn('reviews.reviewer_id', 'marketplace_deals.buyer_id');
            })
            ->with(['buyer', 'seller', 'product'])
            ->chunk(50, function ($deals) use (&$count) {
                foreach ($deals as $deal) {
                    try {
                        $this->notif->reviewReminder($deal->buyer_id, $deal);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::warning('Gagal mengirim pengingat ulasan', [
                            'deal_id' => $deal->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Berhasil mengirim {$count} pengingat ulasan.");

        return self::SUCCESS;
    }
}
