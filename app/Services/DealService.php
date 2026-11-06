<?php

namespace App\Services;

use App\Models\Conversation;
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
     * QR berlaku singkat (5 menit). Membuat ulang QR membatalkan yang lama.
     */
    public function generateQr(MarketplaceDeal $deal): array
    {
        $token = Str::random(64);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(5);

        $deal->update([
            'completion_token_hash' => hash('sha256', $token),
            'completion_code_hash' => hash('sha256', $code),
            'completion_token_expires_at' => $expires,
            'completion_attempts' => 0,
            'completion_locked_until' => null,
        ]);

        return [
            'token' => $token,
            'code' => $code,
            'expires_at' => $expires->toIso8601String(),
        ];
    }

    /**
     * Menyelesaikan deal lewat QR token atau kode 6 digit.
     * Hanya pembeli pada deal, status harus agreed, token belum kedaluwarsa.
     */
    public function complete(
        MarketplaceDeal $deal,
        string $input,
        bool $isCode = false,
        ?float $lat = null,
        ?float $lng = null,
        ?int $buyerId = null,
    ): MarketplaceDeal {
        $outcome = DB::transaction(function () use ($deal, $input, $isCode, $lat, $lng, $buyerId) {
            /** @var MarketplaceDeal $deal */
            $deal = MarketplaceDeal::lockForUpdate()->findOrFail($deal->id);

            if ($buyerId !== null && $deal->buyer_id !== $buyerId) {
                throw new \RuntimeException('Hanya pembeli yang dapat menyelesaikan transaksi.', 403);
            }

            if ($deal->status !== 'agreed') {
                throw new \RuntimeException('Status deal tidak valid.', 409);
            }

            if ($deal->completion_locked_until && $deal->completion_locked_until->isFuture()) {
                throw new \RuntimeException('Kode terkunci sementara. Coba lagi nanti.', 423);
            }

            if (! $deal->completion_token_expires_at || $deal->completion_token_expires_at->isPast()) {
                throw new \RuntimeException('QR sudah kedaluwarsa. Minta penjual membuat ulang.', 422);
            }

            $hash = $isCode
                ? $deal->completion_code_hash
                : $deal->completion_token_hash;

            $expectedHash = hash('sha256', $input);

            if (! $hash || ! hash_equals((string) $hash, $expectedHash)) {
                $attempts = $deal->completion_attempts + 1;
                $update = ['completion_attempts' => $attempts];

                if ($attempts >= 5) {
                    $update['completion_locked_until'] = now()->addMinutes(15);
                }

                // Simpan percobaan gagal, lalu lempar error setelah commit.
                $deal->update($update);

                return [
                    'deal' => null,
                    'error' => 'Kode atau token tidak valid.',
                ];
            }

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($deal->product_id);

            $deal->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_lat' => $lat,
                'completed_lng' => $lng,
                'completion_token_hash' => null,
                'completion_code_hash' => null,
                'completion_token_expires_at' => null,
                'completion_locked_until' => null,
            ]);

            $product->update([
                'status' => 'sold',
            ]);

            $conversation = Conversation::lockForUpdate()->findOrFail($deal->conversation_id);
            $this->createSystemMessage(
                $conversation,
                $deal->buyer_id,
                'Transaksi selesai. Kesepakatan '.$this->rupiah((float) $deal->agreed_price),
            );
            $conversation->touch();

            return [
                'deal' => $deal->fresh(),
                'error' => null,
            ];
        });

        if ($outcome['error'] !== null) {
            throw new \RuntimeException($outcome['error'], 422);
        }

        /** @var MarketplaceDeal $completedDeal */
        $completedDeal = $outcome['deal'];

        // Notifikasi setelah commit.
        $this->notif->dealCompleted($completedDeal->buyer_id, $completedDeal);
        $this->notif->dealCompleted($completedDeal->seller_id, $completedDeal);

        return $completedDeal;
    }

    /**
     * Membatalkan deal agreed dan mengembalikan produk ke etalase.
     */
    public function cancel(MarketplaceDeal $deal, int $cancelledBy, ?string $note = null): MarketplaceDeal
    {
        $deal = DB::transaction(function () use ($deal, $cancelledBy, $note) {
            /** @var MarketplaceDeal $deal */
            $deal = MarketplaceDeal::lockForUpdate()->findOrFail($deal->id);

            if ($deal->buyer_id !== $cancelledBy && $deal->seller_id !== $cancelledBy) {
                throw new \RuntimeException('Unauthorized', 403);
            }

            if ($deal->status !== 'agreed') {
                throw new \RuntimeException('Hanya deal berstatus agreed yang bisa dibatalkan.', 409);
            }

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($deal->product_id);

            if ($product->status === 'reserved') {
                $product->update(['status' => 'available']);
            }

            $deal->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancel_reason' => $note ? Str::limit($note, 250) : 'Dibatalkan oleh pengguna',
                'cancel_note' => $note,
                'completion_token_hash' => null,
                'completion_code_hash' => null,
                'completion_token_expires_at' => null,
                'completion_locked_until' => null,
            ]);

            $conversation = Conversation::lockForUpdate()->findOrFail($deal->conversation_id);
            $this->createSystemMessage(
                $conversation,
                $cancelledBy,
                'Kesepakatan deal dibatalkan'.($note ? '. Alasan: '.$note : ''),
            );
            $conversation->touch();

            return $deal->fresh();
        });

        $otherId = $deal->buyer_id === $cancelledBy ? $deal->seller_id : $deal->buyer_id;
        $this->notif->dealCancelled($otherId, $deal, $deal->cancel_reason);

        return $deal;
    }

    private function createSystemMessage(Conversation $conversation, int $senderId, string $body): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'type' => 'system',
            'message' => $body,
        ]);
    }

    private function rupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }
}
