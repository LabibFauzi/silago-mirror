<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\Conversation;
use App\Models\ConversationPresence;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NotificationService menyimpan notifikasi in-app dan (bila diaktifkan)
 * mengirim push FCM. Kegagalan notifikasi tidak boleh membatalkan aksi utama.
 */
class NotificationService
{
    public function send(
        int $userId,
        string $title,
        string $body,
        string $type,
        ?int $referenceId = null,
        array $data = [],
    ): ?Notification {
        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => Str::limit($title, 150, ''),
                'body' => Str::limit($body, 255, ''),
                'type' => $type,
                'reference_id' => $referenceId,
                'data' => $data ?: null,
            ]);

            if (config('services.fcm.enabled', false)) {
                SendPushNotification::dispatch(
                    $userId,
                    $title,
                    $body,
                    array_merge($data, [
                        'type' => $type,
                        'reference_id' => $referenceId,
                    ]),
                )->afterCommit();
            }

            return $notification;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ---- Chat ----

    public function newMessage(int $userId, Conversation $conversation, string $senderName): void
    {
        // Jangan buat notifikasi bila penerima sedang membuka percakapan ini.
        if ($this->isViewingConversation($userId, $conversation->id)) {
            return;
        }

        $existing = Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'new_message')
            ->where('reference_id', $conversation->id)
            ->where('is_read', false)
            ->latest('id')
            ->first();

        $count = 1;
        if ($existing) {
            $count = (int) (($existing->data['count'] ?? 0) + 1);
        }

        $body = $count > 1
            ? "{$count} pesan baru dari {$senderName}"
            : "{$senderName} mengirim pesan baru";

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->buyer_id === $userId
                ? $conversation->seller_id
                : $conversation->buyer_id,
            'count' => $count,
        ];

        if ($existing) {
            $existing->update([
                'title' => 'Pesan baru',
                'body' => Str::limit($body, 255, ''),
                'data' => $data,
            ]);

            return;
        }

        $this->send($userId, 'Pesan baru', $body, 'new_message', $conversation->id, $data);
    }

    public function newOffer(
        int $userId,
        Conversation $conversation,
        Message $offer,
        string $offererName,
    ): void {
        $price = $this->rupiah((float) $offer->offer_price);
        $isCounter = $offer->parent_offer_id !== null;

        $body = $isCounter
            ? "{$offererName} mengirim penawaran balik {$price}"
            : "{$offererName} mengajukan penawaran {$price}";

        $this->updateOrCreateUnread(
            $userId,
            'new_offer',
            $conversation->id,
            'Penawaran baru',
            $body,
            [
                'conversation_id' => $conversation->id,
                'offer_id' => $offer->id,
                'price' => (float) $offer->offer_price,
            ],
        );
    }

    public function offerRejected(int $userId, Conversation $conversation, ?Message $offer = null): void
    {
        $body = $offer?->offer_price
            ? 'Penawaran '.$this->rupiah((float) $offer->offer_price).' ditolak.'
            : 'Penawaran kamu ditolak. Kamu masih bisa mengajukan harga lain.';

        $this->updateOrCreateUnread(
            $userId,
            'offer_rejected',
            $conversation->id,
            'Penawaran ditolak',
            $body,
            [
                'conversation_id' => $conversation->id,
                'offer_id' => $offer?->id,
            ],
        );
    }

    // ---- Deal ----

    public function dealAgreed(int $userId, MarketplaceDeal $deal): void
    {
        $price = $this->rupiah((float) ($deal->agreed_price ?? $deal->current_offer_price));
        $body = "Harga {$price} telah disepakati.";

        $this->updateOrCreateUnread(
            $userId,
            'deal_agreed',
            $deal->id,
            'Penawaran disetujui',
            $body,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
                'price' => (float) ($deal->agreed_price ?? $deal->current_offer_price),
            ],
        );
    }

    public function dealRenegotiated(int $userId, MarketplaceDeal $deal): void
    {
        $productName = $deal->product?->name ?? 'barang ini';
        $body = "Kesepakatan untuk \"{$productName}\" dibuka kembali. Kamu bisa menawar lagi.";

        $this->updateOrCreateUnread(
            $userId,
            'deal_renegotiated',
            $deal->id,
            'Nego dibuka kembali',
            $body,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
            ],
        );
    }

    public function dealCancelled(int $userId, MarketplaceDeal $deal, ?string $reason = null): void
    {
        $productName = $deal->product?->name ?? 'barang ini';
        $body = "Kesepakatan untuk \"{$productName}\" dibatalkan.";

        if ($reason) {
            $body .= ' Alasan: '.$reason;
        }

        $this->updateOrCreateUnread(
            $userId,
            'deal_cancelled',
            $deal->id,
            'Kesepakatan dibatalkan',
            $body,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
            ],
        );
    }

    public function dealCompleted(int $userId, MarketplaceDeal $deal): void
    {
        $productName = $deal->product?->name ?? 'barang ini';
        $body = "Transaksi \"{$productName}\" telah selesai.";

        $this->updateOrCreateUnread(
            $userId,
            'deal_completed',
            $deal->id,
            'Transaksi selesai',
            $body,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
            ],
        );
    }

    public function codReminder(int $userId, MarketplaceDeal $deal): void
    {
        $existsToday = Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'cod_reminder')
            ->where('reference_id', $deal->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($existsToday) {
            return;
        }

        $productName = $deal->product?->name ?? 'barang ini';
        $this->send(
            $userId,
            'Jangan lupa COD hari ini',
            "Kesepakatan \"{$productName}\" belum diselesaikan. Atur waktu COD lewat chat.",
            'cod_reminder',
            $deal->id,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
            ],
        );
    }

    // ---- Review ----

    public function reviewReminder(int $userId, MarketplaceDeal $deal): void
    {
        $alreadySent = Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'review_reminder')
            ->where('reference_id', $deal->id)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $sellerName = $deal->seller?->name ?? 'penjual';
        $this->send(
            $userId,
            'Tulis ulasan',
            "Bagaimana transaksi kamu dengan {$sellerName}?",
            'review_reminder',
            $deal->id,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
            ],
        );
    }

    public function reviewReceived(
        int $userId,
        MarketplaceDeal $deal,
        string $reviewerName,
        int $rating,
        ?int $reviewId = null,
    ): void {
        $this->send(
            $userId,
            "Ulasan dari {$reviewerName}",
            "Kamu menerima ulasan {$rating} bintang.",
            'review_received',
            $deal->id,
            [
                'conversation_id' => $deal->conversation_id,
                'deal_id' => $deal->id,
                'review_id' => $reviewId,
                'rating' => $rating,
            ],
        );
    }

    // ---- Laporan ----

    public function reportUpdated(Report $report, string $status): void
    {
        $deal = $report->marketplaceDeal;
        $data = [
            'report_id' => $report->id,
            'deal_id' => $deal?->id,
            'conversation_id' => $deal?->conversation_id,
        ];

        $statusText = $status === 'resolved' ? 'diselesaikan' : 'ditolak';

        $this->send(
            $report->reporter_id,
            'Laporan diperbarui',
            "Laporan kamu telah {$statusText} oleh admin.",
            'report_updated',
            $deal?->id,
            $data,
        );

        if ($report->reported_user_id && $report->reported_user_id !== $report->reporter_id) {
            $this->send(
                $report->reported_user_id,
                'Laporan diperbarui',
                "Laporan yang melibatkan transaksi kamu telah {$statusText} oleh admin.",
                'report_updated',
                $deal?->id,
                $data,
            );
        }
    }

    // ---- Admin / katalog ----

    public function postDisetujui(int $userId, int $productId): void
    {
        $name = Product::whereKey($productId)->value('name') ?? 'barang kamu';
        $this->send(
            $userId,
            'Barang disetujui',
            "Barang \"{$name}\" telah disetujui dan kini tayang di katalog.",
            'product_approved',
            $productId,
            ['product_id' => $productId],
        );
    }

    public function postDitolak(int $userId, int $productId, string $alasan): void
    {
        $name = Product::whereKey($productId)->value('name') ?? 'barang kamu';
        $this->send(
            $userId,
            'Barang ditolak',
            "Barang \"{$name}\" ditolak. Alasan: {$alasan}",
            'product_rejected',
            $productId,
            ['product_id' => $productId],
        );
    }

    public function barangDiarsipkan(int $userId, int $productId): void
    {
        $name = Product::whereKey($productId)->value('name') ?? 'barang kamu';
        $this->send(
            $userId,
            'Barang diarsipkan',
            "Barang \"{$name}\" telah melewati masa tayang dan diarsipkan.",
            'product_archived',
            $productId,
            ['product_id' => $productId],
        );
    }

    public function akunDisuspend(int $userId): void
    {
        $this->send(
            $userId,
            'Akun ditangguhkan',
            'Akun kamu telah ditangguhkan. Hubungi dukungan untuk informasi lebih lanjut.',
            'account_suspended',
        );
    }

    private function updateOrCreateUnread(
        int $userId,
        string $type,
        int $referenceId,
        string $title,
        string $body,
        array $data,
    ): void {
        $existing = Notification::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('reference_id', $referenceId)
            ->where('is_read', false)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update([
                'title' => Str::limit($title, 150, ''),
                'body' => Str::limit($body, 255, ''),
                'data' => $data ?: null,
            ]);

            return;
        }

        $this->send($userId, $title, $body, $type, $referenceId, $data);
    }

    private function isViewingConversation(int $userId, int $conversationId): bool
    {
        return ConversationPresence::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('last_seen_at', '>=', now()->subSeconds(20))
            ->exists();
    }

    private function rupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }
}
