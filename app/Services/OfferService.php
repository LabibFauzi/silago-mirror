<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * OfferService: mengelola penawaran harga (offer) dan negosiasi.
 *
 * Satu percakapan hanya boleh punya satu offer pending pada satu waktu.
 * Penawaran pertama pada sebuah percakapan otomatis membuat deal berstatus
 * negotiating sehingga percakapan tersebut tidak ikut dibersihkan oleh
 * chat:purge.
 */
class OfferService
{
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
            /** @var Conversation $conversation */
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);

            if ($conversation->buyer_id !== $senderId && $conversation->seller_id !== $senderId) {
                throw new \RuntimeException('Kamu bukan peserta percakapan ini.', 403);
            }

            if ($price <= 0) {
                throw new \RuntimeException('Harga penawaran tidak valid.', 422);
            }

            // Hanya satu offer pending per percakapan.
            $existing = Message::where('conversation_id', $conversation->id)
                ->where('type', 'offer')
                ->where('offer_status', 'pending')
                ->lockForUpdate()
                ->first();

            // Aturan giliran: ronde baru (tidak ada penawaran pending) hanya
            // boleh dimulai pembeli. Penjual hanya boleh membalas/meng-counter
            // penawaran pembeli.
            if (! $existing && (int) $conversation->buyer_id !== $senderId) {
                throw new \RuntimeException(
                    'Penawaran baru hanya bisa dimulai oleh pembeli.',
                    403,
                );
            }

            if ($existing) {
                // Penawaran yang sudah dikirim tidak dapat diubah atau ditarik.
                if ((int) $existing->sender_id === $senderId) {
                    throw new \RuntimeException(
                        'Penawaran kamu masih menunggu jawaban lawan.',
                        409,
                    );
                }

                // Balasan dari lawan bicara otomatis menjadi counter offer.
                if ($parentOfferId && (int) $parentOfferId !== (int) $existing->id) {
                    throw new \RuntimeException('Penawaran yang dibalas sudah tidak tersedia.', 409);
                }

                $existing->update(['offer_status' => 'countered']);
                $parentOfferId = (int) $existing->id;
            }

            // Cari deal negosiasi yang sedang berjalan.
            $deal = MarketplaceDeal::where('conversation_id', $conversation->id)
                ->where('status', 'negotiating')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $deal) {
                $hasActiveDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
                    ->whereIn('status', ['agreed', 'completed'])
                    ->exists();

                if ($hasActiveDeal) {
                    throw new \RuntimeException(
                        'Percakapan ini sudah memiliki kesepakatan. Gunakan Nego Ulang dari kartu deal.',
                        409,
                    );
                }

                // Penawaran pertama membuka fase negosiasi. Deal cancelled
                // sebelumnya tetap tersimpan sebagai riwayat.
                $deal = MarketplaceDeal::create([
                    'conversation_id' => $conversation->id,
                    'product_id' => $conversation->product_id,
                    'buyer_id' => $conversation->buyer_id,
                    'seller_id' => $conversation->seller_id,
                    'quantity' => 1,
                    'agreed_price' => null,
                    'current_offer_price' => $price,
                    'status' => 'negotiating',
                ]);
            } else {
                $deal->update(['current_offer_price' => $price]);
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'type' => 'offer',
                'message' => $note,
                'offer_price' => $price,
                'offer_status' => 'pending',
                'parent_offer_id' => $parentOfferId,
            ]);

            // Percakapan yang pernah masuk nego tidak lagi dihapus otomatis.
            $conversation->update(['type' => 'transaction']);
            $conversation->touch();

            return $message;
        });
    }

    /**
     * Menerima penawaran. Deal negotiating berubah menjadi agreed dan produk
     * dikunci sementara sampai transaksi selesai.
     */
    public function acceptOffer(Message $offer, int $acceptorId, int $quantity = 1): MarketplaceDeal
    {
        return DB::transaction(function () use ($offer, $acceptorId, $quantity) {
            /** @var Message $offer */
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->type !== 'offer') {
                throw new \RuntimeException('Pesan ini bukan penawaran.', 422);
            }

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak tersedia.', 409);
            }

            if ((int) $offer->sender_id === $acceptorId) {
                throw new \RuntimeException('Kamu tidak dapat menerima penawaran sendiri.', 403);
            }

            /** @var Conversation $conversation */
            $conversation = Conversation::lockForUpdate()->findOrFail($offer->conversation_id);

            if ($conversation->buyer_id !== $acceptorId && $conversation->seller_id !== $acceptorId) {
                throw new \RuntimeException('Kamu bukan peserta percakapan ini.', 403);
            }

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($conversation->product_id);

            if ($product->verification_status !== 'verified' || $product->status !== 'available') {
                throw new \RuntimeException('Produk sudah tidak tersedia untuk deal.', 409);
            }

            /** @var MarketplaceDeal|null $deal */
            $deal = MarketplaceDeal::where('conversation_id', $conversation->id)
                ->where('status', 'negotiating')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $deal) {
                $hasActiveDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
                    ->whereIn('status', ['agreed', 'completed'])
                    ->lockForUpdate()
                    ->exists();

                if ($hasActiveDeal) {
                    throw new \RuntimeException('Sudah ada kesepakatan aktif pada percakapan ini.', 409);
                }

                $deal = MarketplaceDeal::create([
                    'conversation_id' => $conversation->id,
                    'product_id' => $conversation->product_id,
                    'buyer_id' => $conversation->buyer_id,
                    'seller_id' => $conversation->seller_id,
                    'quantity' => $quantity,
                    'agreed_price' => null,
                    'current_offer_price' => $offer->offer_price,
                    'status' => 'negotiating',
                ]);
            }

            $now = now();

            $offer->update(['offer_status' => 'accepted']);

            // Penerima adalah lawan dari pembuat penawaran terakhir, sehingga
            // pembuat penawaran dianggap sudah setuju pada harga yang dia ajukan.
            $deal->update([
                'accepted_message_id' => $offer->id,
                'quantity' => $quantity,
                'status' => 'agreed',
                'agreed_price' => $offer->offer_price,
                'current_offer_price' => $offer->offer_price,
                'buyer_agreed' => true,
                'seller_agreed' => true,
                'buyer_agreed_at' => $now,
                'seller_agreed_at' => $now,
                'agreed_at' => $now,
            ]);

            // Produk dikunci di etalase sampai transaksi selesai.
            $product->update(['status' => 'reserved']);

            $this->createSystemMessage(
                $conversation,
                $acceptorId,
                'Kesepakatan Deal '.$this->rupiah((float) $offer->offer_price),
            );

            $conversation->update(['type' => 'transaction']);
            $conversation->touch();

            return $deal->fresh();
        });
    }

    /**
     * Menolak penawaran. Deal tetap negotiating agar pembeli bisa menawar lagi.
     * Alasan penolakan (opsional) dicatat sebagai pesan sistem di chat.
     */
    public function rejectOffer(Message $offer, int $rejectorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($offer, $rejectorId, $reason) {
            /** @var Message $offer */
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak tersedia.', 409);
            }

            if ((int) $offer->sender_id === $rejectorId) {
                throw new \RuntimeException('Kamu tidak dapat menolak penawaran sendiri.', 403);
            }

            $offer->update(['offer_status' => 'rejected']);

            $conversation = Conversation::lockForUpdate()->findOrFail($offer->conversation_id);

            $body = 'Penawaran '.$this->rupiah((float) $offer->offer_price).' ditolak.';
            $trimmedReason = $reason !== null ? trim($reason) : '';
            if ($trimmedReason !== '') {
                $body .= ' Alasan: '.$trimmedReason;
            }

            $this->createSystemMessage($conversation, $rejectorId, $body);

            $conversation->touch();
        });
    }

    /**
     * Menarik (membatalkan) penawaran milik sendiri yang masih pending.
     */
    public function cancelOffer(Message $offer, int $senderId): void
    {
        DB::transaction(function () use ($offer, $senderId) {
            /** @var Message $offer */
            $offer = Message::lockForUpdate()->findOrFail($offer->id);

            if ($offer->type !== 'offer') {
                throw new \RuntimeException('Pesan ini bukan penawaran.', 422);
            }

            if ($offer->offer_status !== 'pending') {
                throw new \RuntimeException('Penawaran sudah tidak dapat dibatalkan.', 409);
            }

            if ((int) $offer->sender_id !== $senderId) {
                throw new \RuntimeException('Kamu hanya dapat membatalkan penawaran sendiri.', 403);
            }

            $offer->update(['offer_status' => 'withdrawn']);

            $conversation = Conversation::lockForUpdate()->findOrFail($offer->conversation_id);
            $conversation->touch();
        });
    }

    /**
     * Nego ulang dari deal agreed: kembali ke fase negosiasi.
     */
    public function renegotiateDeal(MarketplaceDeal $deal, int $userId): MarketplaceDeal
    {
        return DB::transaction(function () use ($deal, $userId) {
            /** @var MarketplaceDeal $deal */
            $deal = MarketplaceDeal::lockForUpdate()->findOrFail($deal->id);

            if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
                throw new \RuntimeException('Unauthorized', 403);
            }

            if ($deal->status !== 'agreed') {
                throw new \RuntimeException('Hanya deal yang sudah disepakati yang bisa dinegosiasikan ulang.', 409);
            }

            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($deal->product_id);

            $deal->update([
                'status' => 'negotiating',
                'accepted_message_id' => null,
                'agreed_price' => null,
                'agreed_at' => null,
                'buyer_agreed' => false,
                'seller_agreed' => false,
                'buyer_agreed_at' => null,
                'seller_agreed_at' => null,
                'completion_token_hash' => null,
                'completion_code_hash' => null,
                'completion_token_expires_at' => null,
                'completion_attempts' => 0,
                'completion_locked_until' => null,
            ]);

            if ($product->status === 'reserved') {
                $product->update(['status' => 'available']);
            }

            $conversation = Conversation::lockForUpdate()->findOrFail($deal->conversation_id);
            $lastPrice = (float) ($deal->current_offer_price ?? 0);
            $this->createSystemMessage(
                $conversation,
                $userId,
                'Nego ulang dibuka'.($lastPrice > 0 ? '. Harga terakhir '.$this->rupiah($lastPrice) : ''),
            );
            $conversation->update(['type' => 'transaction']);
            $conversation->touch();

            return $deal->fresh();
        });
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
