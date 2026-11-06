<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcceptOfferRequest;
use App\Http\Requests\Api\StoreOfferRequest;
use App\Http\Resources\DealResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
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
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validasi harga > 0 dan <= product.price (N3)
        $product = $conversation->product;
        if ($request->price <= 0 || $request->price > $product->price) {
            return response()->json(['message' => 'Harga penawaran tidak valid.'], 400);
        }

        try {
            $offer = $offerService->createOffer($conversation, $userId, $request->price, $request->note, $request->parent_offer_id);
            
            $recipientId = $conversation->buyer_id === $userId ? $conversation->seller_id : $conversation->buyer_id;
            $type = $request->parent_offer_id ? 'offerDibalas' : 'offerBaru';
            
            $notificationService->send($recipientId, $type, [
                'conversation_id' => $conversation->id,
                'offer_id' => $offer->id,
            ]);

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

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($offer->sender_id === $userId) {
            return response()->json(['message' => 'Tidak dapat menyetujui penawaran sendiri.'], 403);
        }

        try {
            $deal = $offerService->acceptOffer($offer, $userId, $request->quantity ?? 1);
            
            $notificationService->send($offer->sender_id, 'offerDisetujui', [
                'deal_id' => $deal->id,
            ]);

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

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($offer->sender_id === $userId) {
            return response()->json(['message' => 'Tidak dapat menolak penawaran sendiri.'], 403);
        }

        try {
            $offerService->rejectOffer($offer, $userId);
            
            $notificationService->send($offer->sender_id, 'offerDitolak', [
                'offer_id' => $offer->id,
            ]);

            return response()->json(['message' => 'Offer rejected'], 200);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
