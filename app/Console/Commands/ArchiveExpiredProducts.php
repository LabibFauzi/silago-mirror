<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mengarsipkan barang yang sudah melewati masa tayang (expires_at).
 * Dijalankan harian via scheduler.
 */
class ArchiveExpiredProducts extends Command
{
    protected $signature   = 'products:archive-expired';
    protected $description = 'Arsipkan barang yang sudah melewati masa tayang';

    public function __construct(private NotificationService $notif)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = 0;

        Product::query()
            ->where('status', 'available')
            ->where('verification_status', 'verified')
            ->where('expires_at', '<=', now())
            ->chunk(100, function ($products) use (&$count) {
                foreach ($products as $product) {
                    try {
                        DB::transaction(function () use ($product) {
                            $product->update(['status' => 'archived']);
                        });

                        $this->notif->barangDiarsipkan($product->seller_id, $product->id);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::error('Gagal mengarsipkan produk', [
                            'product_id' => $product->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Berhasil mengarsipkan {$count} barang.");

        return self::SUCCESS;
    }
}
