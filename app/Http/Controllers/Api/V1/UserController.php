<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, User $user)
    {
        $avg_rating = Review::where('reviewee_id', $user->id)->avg('rating') ?? 0.0;
        $total_reviews = Review::where('reviewee_id', $user->id)->count();
        
        $total_sold = Deal::whereHas('product', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('status', 'completed')->count();

        $total_bought = Deal::where('buyer_id', $user->id)->where('status', 'completed')->count();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email_verified_at' => $user->email_verified_at,
            'avg_rating' => round($avg_rating, 1),
            'total_reviews' => $total_reviews,
            'total_sold' => $total_sold,
            'total_bought' => $total_bought,
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
        $products = Product::where('user_id', $user->id)
            ->where('status', 'available')
            ->where('is_verified', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($products);
    }
}
