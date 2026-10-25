<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * OfferService: mengelola penawaran harga (offer) dan negosiasi.
 * Satu percakapan hanya boleh punya satu offer pending pada satu waktu.
 */
class OfferService
{
    public function __construct(
        private NotificationService $notif,
    ) {}

    /**
     * Membuat penawaran baru atau counter offer.
     */
    public function createOffer(
        Conversation $conversation,
        int $senderId,
        float $price,
        ?string $note = null,
        ?int $parentOfferId = null,
    ): Message {
        return DB::transaction(function () use ($conversation, $senderId, $price, $note, $parentOfferId) {
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);

            $hasActiveDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
                ->where('status', 'agreed')
                ->exists();

            if ($hasActiveDeal) {
                throw new \RuntimeException('Masih ada deal aktif. Selesaikan atau batalkan deal terlebih dahulu.', 409);
            }

            $hasCompletedDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
                ->where('status', 'completed')
                ->exists();

            if ($hasCompletedDeal) {
                throw new \RuntimeException('Transaksi pada percakapan ini sudah selesai.', 409);
            }

            if ($parentOfferId !== null) {
                $parent = Message::where('id', $parentOfferId)
                    ->where('conversation_id', $conversation->id)
                    ->where('type', 'offer')
                    ->first();

                if (! $parent) {
                    throw new \RuntimeException('Penawaran induk tidak ditemukan.', 422);
                }
            }

            // Hanya satu offer pending per percakapan
            $existing = Message::where('conversation_id', $conversation->id)
                ->where('type', 'offer')
                ->where('offer_status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Counter: tandai yang lama sebagai countered
                if ($parentOfferId) {
                    $existing->update(['offer_status' => 'countered']);
                } else {
                    throw new \RuntimeException('Sudah ada penawaran yang menunggu jawaban.', 409);
                }
            }

            $message = Message::create([
                'conversation_id'  => $conversation->id,
                'sender_id'        => $senderId,
                'type'             => 'offer',
                'message'          => $note,
                'offer_price'      => $price,
                'offer_status'     => 'pending',
                'parent_offer_id'  => $parentOfferId,
            ]);

            $conversation->touch(); // update updated_at untuk urutan percakapan

            return $message;
        });
    }

    /**
     * Menerima penawaran: membuat deal agreed dan mengurangi stok.
     */
    public function acceptOffer(Message $offer, int $acceptorId, int $quantity = 1): MarketplaceDeal
    {
        return DB::transaction(function () use ($offer, $acceptorId, $quantity) {
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak tersedia.', 409);
            }

            // Pihak yang boleh menerima: bukan pengirim penawaran
            if ($offer->sender_id === $acceptorId) {
                throw new \RuntimeException('Kamu tidak dapat menerima penawaran sendiri.', 403);
            }

            $conversation = Conversation::lockForUpdate()->findOrFail($offer->conversation_id);
            $product      = Product::lockForUpdate()->findOrFail($conversation->product_id);

            // Cek stok (rules N8)
            if ($product->stock < $quantity) {
                throw new \RuntimeException("Stok tidak mencukupi. Tersedia: {$product->stock}.", 422);
            }

            // Cek tidak ada deal agreed lain di percakapan yang sama (rules T13)
            $existingDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
                ->where('status', 'agreed')
                ->lockForUpdate()
                ->exists();
            if ($existingDeal) {
                throw new \RuntimeException('Sudah ada deal aktif pada percakapan ini.', 409);
            }

            $offer->update(['offer_status' => 'accepted']);

            // Kurangi stok
            $newStock = $product->stock - $quantity;
            $product->update([
                'stock'  => $newStock,
                'status' => $newStock === 0 ? 'sold' : 'available',
            ]);

            $deal = MarketplaceDeal::create([
                'conversation_id'    => $conversation->id,
                'accepted_message_id' => $offer->id,
                'product_id'         => $conversation->product_id,
                'buyer_id'           => $conversation->buyer_id,
                'seller_id'          => $conversation->seller_id,
                'quantity'           => $quantity,
                'agreed_price'       => $offer->offer_price,
                'status'             => 'agreed',
                'agreed_at'          => now(),
            ]);

            $this->addSystemMessage(
                $conversation,
                $acceptorId,
                'Penawaran Rp' . number_format((float) $offer->offer_price, 0, ',', '.')
                    . ' disetujui. Deal telah dibuat.'
            );

            $conversation->touch();

            return $deal;
        });
    }

    /**
     * Menolak penawaran.
     */
    public function rejectOffer(Message $offer, int $rejectorId): void
    {
        DB::transaction(function () use ($offer, $rejectorId) {
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak tersedia.', 409);
            }

            if ($offer->sender_id === $rejectorId) {
                throw new \RuntimeException('Kamu tidak dapat menolak penawaran sendiri.', 403);
            }

            $offer->update(['offer_status' => 'rejected']);

            $conversation = Conversation::findOrFail($offer->conversation_id);
            $this->addSystemMessage($conversation, $rejectorId, 'Penawaran ditolak.');
            $conversation->touch();
        });
    }

    /**
     * Menarik penawaran sendiri yang masih pending.
     */
    public function withdrawOffer(Message $offer, int $senderId): void
    {
        DB::transaction(function () use ($offer, $senderId) {
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak tersedia.', 409);
            }

            if ($offer->sender_id !== $senderId) {
                throw new \RuntimeException('Kamu hanya dapat menarik penawaran sendiri.', 403);
            }

            $offer->update(['offer_status' => 'rejected']);

            $conversation = Conversation::findOrFail($offer->conversation_id);
            $this->addSystemMessage($conversation, $senderId, 'Penawaran ditarik.');
            $conversation->touch();
        });
    }

    private function addSystemMessage(Conversation $conversation, int $senderId, string $message): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $senderId,
            'type'            => 'system',
            'message'         => $message,
        ]);
    }
}
