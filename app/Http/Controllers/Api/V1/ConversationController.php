<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\ConversationPresence;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $query = Conversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            })
            ->with([
                'product.images',
                'buyer',
                'seller',
                'lastMessage',
                'latestDeal',
                'latestOffer',
            ]);

        if ($request->filled('q')) {
            $search = strtolower($request->q);
            $query->whereHas('product', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });
        }

        $conversations = $query->orderByDesc('updated_at')->paginate(20);

        // Calculate unread count per conversation
        $conversations->getCollection()->transform(function ($conversation) use ($userId) {
            $conversation->unread_count = $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->count();
            return $conversation;
        });

        return ConversationResource::collection($conversations);
    }

    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $count = Message::query()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->whereHas('conversation', function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('buyer_id', $userId)
                        ->orWhere('seller_id', $userId);
                });
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function presence(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses.'], 403);
        }

        ConversationPresence::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ],
            ['last_seen_at' => now()],
        );

        return response()->json(['message' => 'Presence diperbarui.']);
    }

    public function markAllRead(Request $request)
    {
        $userId = $request->user()->id;

        Message::query()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->whereHas('conversation', function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('buyer_id', $userId)
                        ->orWhere('seller_id', $userId);
                });
            })
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua pesan ditandai sudah dibaca.']);
    }

    public function store(Request $request, Product $product)
    {
        $userId = $request->user()->id;

        // Rules: bukan barang sendiri (N1)
        if ($product->seller_id === $userId) {
            return response()->json(['message' => 'Tidak dapat mengirim pesan ke barang milik sendiri.'], 403);
        }

        // produk harus verified+available
        if ($product->verification_status !== 'verified' || $product->status !== 'available') {
            return response()->json(['message' => 'Produk belum terverifikasi atau tidak tersedia.'], 403);
        }

        $conversation = Conversation::firstOrCreate([
            'product_id' => $product->id,
            'buyer_id' => $userId,
            'seller_id' => $product->seller_id,
        ]);

        return new ConversationResource($conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        // Pastikan user adalah peserta
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses.'], 403);
        }

        $conversation->load([
            'product.images',
            'buyer',
            'seller',
            'latestOffer',
            'latestDeal',
        ]);

        $lastOffer = $conversation->latestOffer;
        $latestDeal = $conversation->latestDeal;

        // Deal aktif adalah deal negosiasi/kesepakatan terbaru. Untuk riwayat
        // selesai/dibatalkan, mobile memakai latest_deal.
        $activeDeal = $conversation->marketplaceDeals()
            ->whereIn('status', ['negotiating', 'agreed'])
            ->latest('id')
            ->first();

        return response()->json([
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'last_offer' => $lastOffer
                    ? (new MessageResource($lastOffer))->resolve($request)
                    : null,
                'active_deal' => $activeDeal
                    ? (new DealResource($activeDeal->load(['product', 'buyer', 'seller'])))->resolve($request)
                    : null,
                'latest_deal' => $latestDeal
                    ? (new DealResource($latestDeal->load(['product', 'buyer', 'seller'])))->resolve($request)
                    : null,
            ],
        ]);
    }
}
