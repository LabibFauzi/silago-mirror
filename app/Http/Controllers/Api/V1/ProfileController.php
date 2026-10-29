<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserResource;
use App\Models\Favorite;
use App\Models\MarketplaceDeal;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->name;
        }

        // Nomor telepon hanya bisa diubah lewat verifikasi OTP WhatsApp
        // (PhoneVerificationController), jadi tidak di-set di sini.

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $file = $request->file('profile_photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile-photos', $filename, 'public');
            $data['profile_photo'] = $path;
        } elseif ($request->boolean('remove_profile_photo')) {
            // Hapus foto profil bila diminta.
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = null;
        }

        $user->update($data);

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    public function updateFcmToken(Request $request)
    {
        $data = $request->validate(['fcm_token' => 'nullable|string']);
        $token = trim((string) ($data['fcm_token'] ?? ''));
        $request->user()->update(['fcm_token' => $token !== '' ? $token : null]);
        return response()->json(['message' => 'FCM Token updated']);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        $soldDeals = MarketplaceDeal::where('seller_id', $user->id)
            ->where('status', 'completed');
        $boughtDeals = MarketplaceDeal::where('buyer_id', $user->id)
            ->where('status', 'completed');

        $totalSold = (clone $soldDeals)->count();
        $totalBought = (clone $boughtDeals)->count();
        $totalSoldItems = (int) (clone $soldDeals)->sum('quantity');
        $totalBoughtItems = (int) (clone $boughtDeals)->sum('quantity');

        // Nilai transaksi memakai harga kesepakatan x jumlah barang.
        $totalSalesAmount = (float) DB::table('marketplace_deals')
            ->where('seller_id', $user->id)
            ->where('status', 'completed')
            ->sum(DB::raw('agreed_price * quantity'));

        $totalPurchaseAmount = (float) DB::table('marketplace_deals')
            ->where('buyer_id', $user->id)
            ->where('status', 'completed')
            ->sum(DB::raw('agreed_price * quantity'));

        $reviewsReceived = Review::where('reviewee_id', $user->id);
        $avgRating = (float) ((clone $reviewsReceived)->avg('rating') ?? 0);
        $totalReviews = (clone $reviewsReceived)->count();
        $reviewsGiven = Review::where('reviewer_id', $user->id)->count();

        $activeProducts = Product::where('seller_id', $user->id)
            ->where('status', 'available')
            ->where('verification_status', 'verified')
            ->count();

        $totalProducts = Product::where('seller_id', $user->id)->count();

        $totalTransactions = MarketplaceDeal::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        })->count();

        $activeDeals = MarketplaceDeal::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        })
            ->whereIn('status', ['negotiating', 'agreed'])
            ->count();

        $totalFavorites = Favorite::where('user_id', $user->id)->count();

        $cancelledDeals = MarketplaceDeal::where('cancelled_by', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $recentReviews = Review::with([
            'reviewer:id,name,profile_photo',
            'reviewee:id,name,profile_photo',
            'marketplaceDeal.product:id,name,price',
        ])
            ->where('reviewee_id', $user->id)
            ->latest('id')
            ->limit(3)
            ->get();

        return response()->json([
            'avg_rating' => round($avgRating, 1),
            'total_reviews' => $totalReviews,
            'reviews_given' => $reviewsGiven,
            'total_sold' => $totalSold,
            'total_bought' => $totalBought,
            'total_sold_items' => $totalSoldItems,
            'total_bought_items' => $totalBoughtItems,
            'total_sales_amount' => $totalSalesAmount,
            'total_purchase_amount' => $totalPurchaseAmount,
            'balance' => $totalSalesAmount - $totalPurchaseAmount,
            'active_products' => $activeProducts,
            'total_products' => $totalProducts,
            'active_deals' => $activeDeals,
            'total_transactions' => $totalTransactions,
            'cancelled_deals' => $cancelledDeals,
            'total_favorites' => $totalFavorites,
            'recent_reviews' => ReviewResource::collection($recentReviews)->resolve($request),
        ]);
    }

    /**
     * Ulasan yang diterima atau diberikan oleh user yang sedang login.
     */
    public function reviews(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type', 'received');

        $query = Review::with([
            'reviewer:id,name,profile_photo',
            'reviewee:id,name,profile_photo',
            'marketplaceDeal.product:id,name,price',
        ]);

        if ($type === 'given') {
            $query->where('reviewer_id', $user->id);
        } else {
            $query->where('reviewee_id', $user->id);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $reviews = $query->latest('id')->paginate($perPage);

        return ReviewResource::collection($reviews);
    }
}
