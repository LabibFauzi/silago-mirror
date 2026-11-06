<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        return response()->json(new UserResource($request->user()));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->name;
        }
        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $file = $request->file('profile_photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile-photos', $filename, 'public');
            $data['profile_photo'] = $path;
        }

        $user->update($data);

        return response()->json(new UserResource($user->fresh()));
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'FCM Token updated']);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        // avg_rating
        $avg_rating = \App\Models\Review::where('reviewee_id', $user->id)->avg('rating') ?? 0.0;
        $total_reviews = \App\Models\Review::where('reviewee_id', $user->id)->count();
        
        // total_sold, total_bought
        $total_sold = \App\Models\Deal::whereHas('product', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('status', 'completed')->count();

        $total_bought = \App\Models\Deal::where('buyer_id', $user->id)->where('status', 'completed')->count();

        // active_products
        $active_products = \App\Models\Product::where('user_id', $user->id)
            ->where('status', 'available')
            ->where('is_verified', true)
            ->count();
            
        // active_deals
        $active_deals = \App\Models\Deal::where(function($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhereHas('product', function($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            })
            ->where('status', 'agreed')
            ->count();

        // total_favorites
        $total_favorites = \App\Models\Favorite::where('user_id', $user->id)->count();

        return response()->json([
            'avg_rating' => round($avg_rating, 1),
            'total_reviews' => $total_reviews,
            'total_sold' => $total_sold,
            'total_bought' => $total_bought,
            'active_products' => $active_products,
            'active_deals' => $active_deals,
            'total_favorites' => $total_favorites,
        ]);
    }
}
