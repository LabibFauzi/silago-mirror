<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->get(['id', 'name', 'slug', 'icon', 'products_count']);
            
        return response()->json($categories);
    }
}
