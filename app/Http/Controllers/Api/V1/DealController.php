<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelRequest;
use App\Http\Requests\Api\CompleteRequest;
use App\Http\Resources\DealResource;
use App\Models\MarketplaceDeal;
use App\Services\DealService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $query = MarketplaceDeal::where(function ($q) use ($userId) {
            $q->where('buyer_id', $userId)
              ->orWhere('seller_id', $userId);
        })->with(['product', 'buyer', 'seller']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DealResource::collection($query->paginate(15));
    }

    public function show(Request $request, MarketplaceDeal $deal)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $canGenerateQr = $deal->seller_id === $userId && $deal->status === 'agreed';
        $canComplete = $deal->buyer_id === $userId && $deal->status === 'agreed';
        $canCancel = $deal->status === 'agreed';
        
        // Cek apakah user belum mereview
        $hasReviewed = $deal->reviews()->where('reviewer_id', $userId)->exists();
        $canReview = $deal->status === 'completed' && !$hasReviewed;

        return response()->json([
            'data' => new DealResource($deal),
            'actions' => [
                'can_generate_qr' => $canGenerateQr,
                'can_complete' => $canComplete,
                'can_cancel' => $canCancel,
                'can_review' => $canReview,
            ],
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
            // Kita pass token atau code
            $payload = $request->filled('token') ? ['token' => $request->token] : ['code' => $request->code];
            if ($request->filled('lat')) {
                $payload['lat'] = $request->lat;
                $payload['lng'] = $request->lng;
            }

            $deal = $dealService->complete($deal, $payload);
            return new DealResource($deal);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    public function cancel(CancelRequest $request, MarketplaceDeal $deal, DealService $dealService)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $dealService->cancel($deal, $userId, $request->note);
            return response()->json(['message' => 'Deal cancelled'], 200);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json(['message' => $e->getMessage()], $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
