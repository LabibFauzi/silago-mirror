<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\MarketplaceDeal;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, MarketplaceDeal $deal, NotificationService $notificationService)
    {
        $userId = $request->user()->id;

        if ($deal->status !== 'completed') {
            return response()->json(['message' => 'Hanya deal yang selesai yang dapat diulas.'], 400);
        }

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $exists = $deal->reviews()->where('reviewer_id', $userId)->exists();
        if ($exists) {
            return response()->json(['message' => 'Anda sudah memberikan ulasan untuk deal ini.'], 409);
        }

        $revieweeId = $deal->buyer_id === $userId ? $deal->seller_id : $deal->buyer_id;

        $review = DB::transaction(function () use ($request, $deal, $userId, $revieweeId) {
            return $deal->reviews()->create([
                'reviewer_id' => $userId,
                'reviewee_id' => $revieweeId,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
        });

        $notificationService->send($revieweeId, 'ulasan_baru', [
            'deal_id' => $deal->id,
            'review_id' => $review->id,
        ]);

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }
}
