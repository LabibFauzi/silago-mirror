<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceDeal;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, User $user)
    {
        $avg_rating = Review::where('reviewee_id', $user->id)->avg('rating') ?? 0.0;
        $total_reviews = Review::where('reviewee_id', $user->id)->count();
        
        $total_sold = MarketplaceDeal::whereHas('product', function ($q) use ($user) {
            $q->where('seller_id', $user->id);
        })->where('status', 'completed')->count();

        $total_bought = MarketplaceDeal::where('buyer_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $cancelled_deals = MarketplaceDeal::where('cancelled_by', $user->id)
            ->where('status', 'cancelled')
            ->count();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email_verified_at' => $user->email_verified_at,
            'avg_rating' => round($avg_rating, 1),
            'total_reviews' => $total_reviews,
            'total_sold' => $total_sold,
            'total_bought' => $total_bought,
            'cancelled_deals' => $cancelled_deals,
        ]);
    }

    public function reviews(Request $request, User $user)
    {
        $reviews = Review::with('reviewer:id,name')
            ->where('reviewee_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json($reviews);
    }

    public function products(Request $request, User $user)
    {
        $products = Product::where('seller_id', $user->id)
            ->where('status', 'available')
            ->where('verification_status', 'verified')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($products);
    }
}
