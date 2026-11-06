<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Daftar favorit user.
     */
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with(['product.images' => function ($q) {
                $q->orderBy('sort_order', 'asc');
            }, 'product.category', 'product.seller'])
            ->latest('id') // karena updated_at null
            ->paginate(15);
            
        // Ubah dari model Favorite ke format ProductListResource
        $products = $favorites->getCollection()->map(function ($favorite) {
            return $favorite->product;
        });
        
        $favorites->setCollection($products);

        return \App\Http\Resources\ProductListResource::collection($favorites);
    }

    /**
     * Toggle status favorit pada sebuah produk.
     */
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();
        
        $favorite = $user->favorites()->where('product_id', $product->id)->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
        } else {
            $user->favorites()->create([
                'product_id' => $product->id,
            ]);
            $isFavorited = true;
        }

        return response()->json([
            'favorited' => $isFavorited
        ]);
    }
}
