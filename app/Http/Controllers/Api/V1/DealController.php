<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelRequest;
use App\Http\Requests\Api\CompleteRequest;
use App\Http\Resources\DealResource;
use App\Models\MarketplaceDeal;
use App\Services\DealService;
use App\Services\NotificationService;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $role = $request->query('role');

        $query = MarketplaceDeal::query()->with(['product', 'buyer', 'seller']);

        if ($role === 'buyer') {
            $query->where('buyer_id', $userId);
        } elseif ($role === 'seller') {
            $query->where('seller_id', $userId);
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DealResource::collection($query->latest('id')->paginate(15));
    }

    public function show(Request $request, MarketplaceDeal $deal)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke transaksi ini.'], 403);
        }

        $deal->load(['product', 'buyer', 'seller']);
        $resource = (new DealResource($deal))->resolve($request);

        return response()->json([
            'data' => $resource,
            'actions' => $resource['actions'],
        ]);
    }

    public function generateQr(Request $request, MarketplaceDeal $deal, DealService $dealService)
    {
        $userId = $request->user()->id;

        if ($deal->seller_id !== $userId) {
            return response()->json(['message' => 'Hanya penjual yang dapat membuat QR Code.'], 403);
        }

        if ($deal->status !== 'agreed') {
            return response()->json(['message' => 'Status deal tidak valid.'], 400);
        }

        try {
            $qrData = $dealService->generateQr($deal);
            
            return response()->json([
                'token' => $qrData['token'],
                'code' => $qrData['code'],
                'expires_at' => $qrData['expires_at'],
                'qr_data' => 'silago://deal/' . $deal->id . '/complete/' . $qrData['token'],
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function complete(CompleteRequest $request, MarketplaceDeal $deal, DealService $dealService)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId) {
            return response()->json(['message' => 'Hanya pembeli yang dapat menyelesaikan transaksi.'], 403);
        }

        try {
            $isCode = $request->filled('code');
            $input = $isCode ? (string) $request->code : (string) $request->token;

            $deal = $dealService->complete(
                $deal,
                $input,
                $isCode,
                $request->filled('lat') ? (float) $request->lat : null,
                $request->filled('lng') ? (float) $request->lng : null,
                $userId,
            );

            return new DealResource($deal->load(['product', 'buyer', 'seller']));
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function cancel(CancelRequest $request, MarketplaceDeal $deal, DealService $dealService)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke transaksi ini.'], 403);
        }

        try {
            $cancelled = $dealService->cancel($deal, $userId, $request->note);

            return response()->json([
                'message' => 'Deal dibatalkan.',
                'data' => new DealResource($cancelled->load(['product', 'buyer', 'seller'])),
            ], 200);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function renegotiate(
        Request $request,
        MarketplaceDeal $deal,
        OfferService $offerService,
        NotificationService $notificationService,
    ) {
        $userId = $request->user()->id;

        try {
            $updated = $offerService->renegotiateDeal($deal, $userId);

            $otherId = $updated->buyer_id === $userId
                ? $updated->seller_id
                : $updated->buyer_id;

            $notificationService->dealRenegotiated($otherId, $updated);

            return new DealResource($updated->load(['product', 'buyer', 'seller']));
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
