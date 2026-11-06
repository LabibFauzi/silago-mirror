<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Menghapus percakapan chat biasa yang tidak pernah masuk negosiasi/deal.
 *
 * Aturan:
 * - tidak punya pesan offer,
 * - tidak punya baris marketplace_deals (status apa pun),
 * - aktivitas terakhir lebih dari 24 jam.
 *
 * Percakapan yang pernah nego, deal, selesai, cancelled, atau reported tidak
 * akan pernah masuk kriteria karena selalu memiliki baris deal.
 */
class ChatPurgeService
{
    public function purge(int $hours = 24): array
    {
        $threshold = now()->subHours(max(1, $hours));

        $deletedConversations = 0;
        $deletedMessages = 0;
        $deletedFiles = 0;

        Conversation::query()
            ->where('updated_at', '<', $threshold)
            ->whereDoesntHave('messages', function ($query) {
                $query->where('type', 'offer');
            })
            ->whereDoesntHave('marketplaceDeals')
            ->select('id')
            ->chunkById(100, function ($conversations) use (
                $threshold,
                &$deletedConversations,
                &$deletedMessages,
                &$deletedFiles,
            ) {
                foreach ($conversations as $row) {
                    $result = DB::transaction(function () use ($row, $threshold) {
                        /** @var Conversation|null $conversation */
                        $conversation = Conversation::whereKey($row->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $conversation || $conversation->updated_at >= $threshold) {
                            return null;
                        }

                        // Re-cek di dalam transaksi agar tidak menghapus chat
                        // yang baru saja masuk nego.
                        if ($conversation->messages()->where('type', 'offer')->exists()) {
                            return null;
                        }

                        if ($conversation->marketplaceDeals()->exists()) {
                            return null;
                        }

                        $messages = $conversation->messages()->get([
                            'id',
                            'image_path',
                            'image_thumbnail',
                        ]);

                        $paths = $messages
                            ->flatMap(fn ($message) => [
                                $message->getRawOriginal('image_path'),
                                $message->getRawOriginal('image_thumbnail'),
                            ])
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        $messageCount = $messages->count();
                        $disk = Storage::disk('public');
                        $existing = array_values(array_filter(
                            $paths,
                            fn ($path) => ! str_starts_with($path, 'http://')
                                && ! str_starts_with($path, 'https://')
                                && $disk->exists($path),
                        ));

                        // Urutan: hapus pesan, file gambar, lalu conversation.
                        $conversation->messages()->delete();

                        // Notifikasi chat yang menunjuk ke percakapan ini ikut
                        // dibersihkan agar tidak membuka layar yang sudah hilang.
                        Notification::query()
                            ->where('reference_id', $conversation->id)
                            ->whereIn('type', [
                                'new_message',
                                'new_offer',
                                'offer_rejected',
                                'deal_renegotiated',
                                'deal_cancelled',
                                // tipe lama sebelum kode notifikasi diseragamkan
                                'pesan_baru',
                                'offer_baru',
                                'offer_dibalas',
                                'offer_ditolak',
                                'nego_ulang',
                            ])
                            ->delete();

                        if ($existing !== []) {
                            $disk->delete($existing);
                        }

                        $conversation->delete();

                        return [
                            'message_count' => $messageCount,
                            'file_count' => count($existing),
                        ];
                    });

                    if ($result === null) {
                        continue;
                    }

                    $deletedConversations++;
                    $deletedMessages += $result['message_count'];
                    $deletedFiles += $result['file_count'];
                }
            });

        return [
            'threshold' => $threshold->toIso8601String(),
            'conversations' => $deletedConversations,
            'messages' => $deletedMessages,
            'files' => $deletedFiles,
        ];
    }
}
