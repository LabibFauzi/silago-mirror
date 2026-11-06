<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
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
            ->with(['product.images', 'buyer', 'seller', 'lastMessage']);

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
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->load(['product.images', 'buyer', 'seller']);
        
        $lastOffer = $conversation->messages()
            ->where('type', 'offer')
            ->latest()
            ->first();

        // Cari active deal
        $activeDeal = MarketplaceDeal::where('product_id', $conversation->product_id)
            ->where('buyer_id', $conversation->buyer_id)
            ->where('seller_id', $conversation->seller_id)
            ->whereIn('status', ['agreed'])
            ->first();

        return response()->json([
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'last_offer' => $lastOffer ? [
                    'id' => $lastOffer->id,
                    'offer_price' => $lastOffer->offer_price,
                    'offer_status' => $lastOffer->offer_status,
                ] : null,
                'active_deal' => $activeDeal ? [
                    'id' => $activeDeal->id,
                    'status' => $activeDeal->status,
                ] : null,
            ]
        ]);
    }
}
