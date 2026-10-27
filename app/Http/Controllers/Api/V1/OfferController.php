<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcceptOfferRequest;
use App\Http\Requests\Api\StoreOfferRequest;
use App\Http\Resources\DealResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Services\OfferService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function store(StoreOfferRequest $request, Conversation $conversation, OfferService $offerService, NotificationService $notificationService)
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke percakapan ini.'], 403);
        }

        // Validasi harga > 0 dan <= product.price (N3)
        $product = $conversation->product;
        if ($request->price <= 0 || $request->price > $product->price) {
            return response()->json(['message' => 'Harga penawaran tidak valid.'], 400);
        }

        if ($product->verification_status !== 'verified' || $product->status !== 'available') {
            return response()->json(['message' => 'Produk sudah tidak tersedia untuk ditawar.'], 409);
        }

        $hasActiveDeal = MarketplaceDeal::where('conversation_id', $conversation->id)
            ->whereIn('status', ['agreed', 'completed'])
            ->exists();

        if ($hasActiveDeal) {
            return response()->json([
                'message' => 'Percakapan ini sudah memiliki kesepakatan. Gunakan Nego Ulang dari kartu deal.',
            ], 409);
        }

        try {
            $offer = $offerService->createOffer($conversation, $userId, $request->price, $request->note, $request->parent_offer_id);
            
            $recipientId = $conversation->buyer_id === $userId ? $conversation->seller_id : $conversation->buyer_id;

            $notificationService->newOffer(
                $recipientId,
                $conversation,
                $offer,
                (string) $request->user()->name,
            );

            return (new MessageResource($offer))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function accept(AcceptOfferRequest $request, Message $offer, OfferService $offerService, NotificationService $notificationService)
    {
        $conversation = $offer->conversation;
        $userId = $request->user()->id;

        if (! $conversation) {
            return response()->json(['message' => 'Penawaran tidak ditemukan.'], 404);
        }

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke percakapan ini.'], 403);
        }

        if ($offer->sender_id === $userId) {
            return response()->json(['message' => 'Tidak dapat menyetujui penawaran sendiri.'], 403);
        }

        try {
            $deal = $offerService->acceptOffer($offer, $userId, $request->quantity ?? 1);

            // Kedua pihak menerima notifikasi kesepakatan.
            $notificationService->dealAgreed($deal->buyer_id, $deal);
            $notificationService->dealAgreed($deal->seller_id, $deal);

            return (new DealResource($deal))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function reject(Request $request, Message $offer, OfferService $offerService, NotificationService $notificationService)
    {
        $conversation = $offer->conversation;
        $userId = $request->user()->id;

        if (! $conversation) {
            return response()->json(['message' => 'Penawaran tidak ditemukan.'], 404);
        }

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke percakapan ini.'], 403);
        }

        if ($offer->sender_id === $userId) {
            return response()->json(['message' => 'Tidak dapat menolak penawaran sendiri.'], 403);
        }

        try {
            $offerService->rejectOffer($offer, $userId);

            $notificationService->offerRejected($offer->sender_id, $conversation, $offer);

            return response()->json(['message' => 'Penawaran ditolak.'], 200);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
