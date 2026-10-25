<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_images_keep_absolute_urls_and_resolve_relative_paths(): void
    {
        $seller = User::factory()->create(['status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);

        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk dengan gambar',
            'price' => 50000,
            'stock' => 1,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);

        $absoluteUrl = 'https://via.placeholder.com/400x300?text=Product+1';

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $absoluteUrl,
            'sort_order' => 0,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/contoh.png',
            'sort_order' => 1,
        ]);

        $this->actingAs($buyer, 'sanctum');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.cover_image', $absoluteUrl)
            ->assertJsonPath('data.0.images.0.url', $absoluteUrl)
            ->assertJsonPath('data.0.images.1.url', '/storage/products/contoh.png');

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.cover_image', $absoluteUrl)
            ->assertJsonPath('data.images.0.url', $absoluteUrl)
            ->assertJsonPath('data.images.1.url', '/storage/products/contoh.png');
    }
}
