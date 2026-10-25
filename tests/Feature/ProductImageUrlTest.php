<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_database_stored_product_image_is_served_from_media_route(): void
    {
        $seller = User::factory()->create(['status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);

        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik-db',
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk gambar database',
            'price' => 75000,
            'stock' => 1,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);

        // PNG 1x1 transparan.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        );

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => null,
            'has_data' => true,
            'mime_type' => 'image/png',
            'sort_order' => 0,
        ]);
        $image->data()->create(['data' => base64_encode($png)]);

        $expectedUrl = '/media/product-images/' . $image->id;

        $this->actingAs($buyer, 'sanctum');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.cover_image', $expectedUrl)
            ->assertJsonPath('data.0.images.0.url', $expectedUrl);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.cover_image', $expectedUrl)
            ->assertJsonPath('data.images.0.url', $expectedUrl);

        $response = $this->get($expectedUrl);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame($png, $response->getContent());
    }

    public function test_product_upload_stores_image_in_database_instead_of_disk(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['status' => 'active']);
        $category = Category::create([
            'name' => 'Fashion',
            'slug' => 'fashion-upload',
        ]);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/products', [
                'name' => 'Produk upload database',
                'description' => 'Deskripsi produk',
                'condition' => 'Bekas',
                'price' => 25000,
                'stock' => 1,
                'category_id' => $category->id,
                'photos' => [
                    UploadedFile::fake()->image('foto.png', 40, 30),
                ],
            ])
            ->assertCreated();

        $image = ProductImage::firstOrFail();

        $this->assertTrue($image->has_data);
        $this->assertNull($image->image_path);
        $this->assertSame('image/png', $image->mime_type);
        $this->assertNotNull($image->data);
        $this->assertNotEmpty($image->data->data);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
}
