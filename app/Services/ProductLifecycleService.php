<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Siklus hidup produk: hapus (soft delete), nonaktifkan, dan tandai terjual.
 *
 * Aturan:
 * - Deal aktif (agreed/shipped/received) memblokir hapus produk dan ubah harga.
 * - Hapus memakai soft delete agar riwayat deal/ulasan tetap punya referensi.
 * - Saat produk dihapus, semua penawaran pending otomatis rejected dan lawan
 *   chat mendapat notifikasi "Produk sudah tidak tersedia".
 */
class ProductLifecycleService
{
    /** Status deal yang dianggap masih aktif. */
    public const ACTIVE_DEAL_STATUSES = ['agreed', 'shipped', 'received'];

    /** Status produk yang boleh dipilih penjual. */
    public const SELLER_STATUSES = ['available', 'sold', 'inactive'];

    public function hasActiveDeal(int $productId): bool
    {
        return MarketplaceDeal::query()
            ->where('product_id', $productId)
            ->whereIn('status', self::ACTIVE_DEAL_STATUSES)
            ->exists();
    }

    /**
     * Soft delete produk + tolak penawaran pending.
     *
     * @return array<int, array{user_id:int, conversation_id:int}>
     *         Penerima notifikasi "produk tidak tersedia".
     */
    public function deleteProduct(Product $product, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($product, $actorId) {
            $locked = Product::withTrashed()->lockForUpdate()->findOrFail($product->id);

            if ($locked->trashed()) {
                return [];
            }

            if ($this->hasActiveDeal($locked->id)) {
                throw new \RuntimeException(
                    'Tidak dapat menghapus barang karena masih ada transaksi aktif. '
                    .'Selesaikan atau batalkan transaksinya dulu.',
                    409,
                );
            }

            $recipients = [];

            $conversations = Conversation::query()
                ->where('product_id', $locked->id)
                ->get();

            foreach ($conversations as $conversation) {
                $pendingIds = Message::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('type', 'offer')
                    ->where('offer_status', 'pending')
                    ->lockForUpdate()
                    ->pluck('id');

                if ($pendingIds->isEmpty()) {
                    continue;
                }

                Message::whereIn('id', $pendingIds)->update([
                    'offer_status' => 'rejected',
                ]);

                foreach ([$conversation->buyer_id, $conversation->seller_id] as $userId) {
                    if ($userId === null || (int) $userId === (int) $actorId) {
                        continue;
                    }

                    $key = $userId.'|'.$conversation->id;
                    $recipients[$key] = [
                        'user_id' => (int) $userId,
                        'conversation_id' => (int) $conversation->id,
                    ];
                }
            }

            // Foto TIDAK dihapus: riwayat transaksi masih membutuhkannya.
            $locked->status = 'inactive';
            $locked->save();
            $locked->delete();

            return array_values($recipients);
        });
    }

    public function updateStatus(Product $product, string $status): Product
    {
        $status = strtolower(trim($status));

        if (! in_array($status, self::SELLER_STATUSES, true)) {
            throw new \RuntimeException('Status barang tidak valid.', 422);
        }

        $product->status = $status;
        $product->save();

        return $product;
    }
}