<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->with(['product.images', 'buyer', 'seller', 'lastMessage', 'latestDeal', 'latestOffer'])
            ->withCount([
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->where('sender_id', '!=', $userId)->whereNull('read_at');
                },
            ]);

        if ($request->filled('q')) {
            $search = '%' . strtolower((string) $request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($product) use ($search) {
                    $product->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('buyer', function ($user) use ($search) {
                    $user->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('seller', function ($user) use ($search) {
                    $user->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        $conversations = $query->orderByDesc('updated_at')->paginate(20);

        return ConversationResource::collection($conversations);
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

        return (new ConversationResource($conversation))->response()->setStatusCode(200);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        // Pastikan user adalah peserta
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->load(['product.images', 'buyer', 'seller', 'lastMessage', 'latestDeal', 'latestOffer']);
        $conversation->loadCount([
            'messages as unread_count' => function ($q) use ($userId) {
                $q->where('sender_id', '!=', $userId)->whereNull('read_at');
            },
        ]);

        $lastOffer = $conversation->latestOffer;

        // Cari active deal
        $activeDeal = MarketplaceDeal::where('product_id', $conversation->product_id)
            ->where('buyer_id', $conversation->buyer_id)
            ->where('seller_id', $conversation->seller_id)
            ->where('status', 'agreed')
            ->latest('id')
            ->first();

        $latestDeal = $conversation->latestDeal;

        return response()->json([
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'last_offer' => $lastOffer ? [
                    'id' => $lastOffer->id,
                    'sender_id' => $lastOffer->sender_id,
                    'offer_price' => $lastOffer->offer_price,
                    'offer_status' => $lastOffer->offer_status,
                    'parent_offer_id' => $lastOffer->parent_offer_id,
                    'message' => $lastOffer->message,
                    'created_at' => $lastOffer->created_at,
                ] : null,
                'active_deal' => $activeDeal ? [
                    'id' => $activeDeal->id,
                    'status' => $activeDeal->status,
                ] : null,
                'latest_deal' => $latestDeal ? [
                    'id' => $latestDeal->id,
                    'status' => $latestDeal->status,
                    'agreed_price' => (float) $latestDeal->agreed_price,
                    'quantity' => $latestDeal->quantity,
                    'completed_at' => $latestDeal->completed_at,
                    'cancelled_at' => $latestDeal->cancelled_at,
                    'cancel_note' => $latestDeal->cancel_note,
                    'actions' => [
                        'can_generate_qr' => $latestDeal->seller_id === $userId && $latestDeal->status === 'agreed',
                        'can_complete' => $latestDeal->buyer_id === $userId && $latestDeal->status === 'agreed',
                        'can_cancel' => $latestDeal->status === 'agreed',
                        'can_review' => $latestDeal->status === 'completed'
                            && ! $latestDeal->reviews()->where('reviewer_id', $userId)->exists(),
                    ],
                ] : null,
            ]
        ]);
    }

    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $count = Message::query()
            ->whereHas('conversation', function ($q) use ($userId) {
                $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAllRead(Request $request)
    {
        $userId = $request->user()->id;

        $count = Message::query()
            ->whereHas('conversation', function ($q) use ($userId) {
                $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Semua pesan ditandai sudah dibaca.',
            'count' => $count,
        ]);
    }
}
