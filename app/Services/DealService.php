<?php

namespace App\Services;

use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DealService: mengelola lifecycle transaksi marketplace.
 * Semua operasi kritis menggunakan DB::transaction + lockForUpdate.
 */
class DealService
{
    public function __construct(
        private NotificationService $notif,
        private SettingService $settings,
    ) {}

    /**
     * Membuat QR token dan kode alternatif 6 digit untuk penjual.
     * Membuat ulang QR membatalkan yang lama.
     */
    public function generateQr(MarketplaceDeal $deal): array
    {
        $token   = Str::random(64);
        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        $deal->update([
            'completion_token_hash'      => hash('sha256', $token),
            'completion_code_hash'       => hash('sha256', $code),
            'completion_token_expires_at' => $expires,
            'completion_attempts'        => 0,
            'completion_locked_until'    => null,
        ]);

        return [
            'token'      => $token,
            'code'       => $code,
            'expires_at' => $expires->toIso8601String(),
        ];
    }

    /**
     * Menyelesaikan deal lewat QR token atau kode 6 digit.
     * Hanya pembeli pada deal, status harus agreed, token belum kedaluwarsa.
     */
    public function complete(MarketplaceDeal $deal, string $input, bool $isCode = false, ?float $lat = null, ?float $lng = null): void
    {
        DB::transaction(function () use ($deal, $input, $isCode, $lat, $lng) {
            /** @var MarketplaceDeal $deal */
            $deal = MarketplaceDeal::lockForUpdate()->findOrFail($deal->id);

            if ($deal->status !== 'agreed') {
                throw new \RuntimeException('Status deal tidak valid.', 409);
            }

            // Cek kunci percobaan
            if ($deal->completion_locked_until && $deal->completion_locked_until->isFuture()) {
                throw new \RuntimeException('Kode terkunci sementara. Coba lagi nanti.', 423);
            }

            $hash = $isCode
                ? $deal->completion_code_hash
                : $deal->completion_token_hash;

            $expectedHash = hash('sha256', $input);

            if (! hash_equals((string) $hash, $expectedHash)) {
                $attempts = $deal->completion_attempts + 1;
                $update   = ['completion_attempts' => $attempts];

                if ($attempts >= 5) {
                    $update['completion_locked_until'] = now()->addMinutes(15);
                }

                $deal->update($update);
                throw new \RuntimeException('Kode atau token tidak valid.', 422);
            }

            // Cek kedaluwarsa
            if ($deal->completion_token_expires_at && $deal->completion_token_expires_at->isPast()) {
                throw new \RuntimeException('QR sudah kedaluwarsa. Minta penjual membuat ulang.', 422);
            }

            $deal->update([
                'status'                     => 'completed',
                'completed_at'               => now(),
                'completed_lat'              => $lat,
                'completed_lng'              => $lng,
                'completion_token_hash'      => null,
                'completion_code_hash'       => null,
                'completion_token_expires_at' => null,
            ]);
        });

        // Notifikasi setelah commit
        $this->notif->transaksiSelesai($deal->buyer_id, $deal->id);
        $this->notif->transaksiSelesai($deal->seller_id, $deal->id);
    }

    /**
     * Membatalkan deal. Mengembalikan stok dan menghanguskan QR.
     */
    public function cancel(MarketplaceDeal $deal, int $cancelledBy, ?string $note = null): void
    {
        DB::transaction(function () use ($deal, $cancelledBy, $note) {
            $deal = MarketplaceDeal::lockForUpdate()->findOrFail($deal->id);

            if ($deal->status !== 'agreed') {
                throw new \RuntimeException('Hanya deal berstatus agreed yang bisa dibatalkan.', 409);
            }

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($deal->product_id);

            $newStock = $product->stock + $deal->quantity;
            $product->update([
                'stock'  => $newStock,
                'status' => 'available', // pulihkan jika sebelumnya sold
            ]);

            $deal->update([
                'status'                     => 'cancelled',
                'cancelled_at'               => now(),
                'cancelled_by'               => $cancelledBy,
                'cancel_note'                => $note,
                'completion_token_hash'      => null,
                'completion_code_hash'       => null,
                'completion_token_expires_at' => null,
            ]);
        });

        $otherId = $deal->buyer_id === $cancelledBy ? $deal->seller_id : $deal->buyer_id;
        $this->notif->transaksiDibatalkan($otherId, $deal->id);
    }
}
