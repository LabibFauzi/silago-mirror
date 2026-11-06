<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\MarketplaceDeal;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_buyer_can_review_a_completed_deal(): void
    {
        [$seller, $buyer, $product, $deal] = $this->createCompletedDeal();

        Sanctum::actingAs($seller);
        $this->postJson('/api/v1/deals/'.$deal->id.'/review', [
            'rating' => 5,
            'comment' => 'Penjual mencoba memberi ulasan.',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Hanya pembeli yang dapat memberikan ulasan.');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/deals/'.$deal->id.'/review', [
            'rating' => 5,
            'comment' => 'Barang sesuai dan penjual ramah.',
        ])->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.reviewer.id', $buyer->id)
            ->assertJsonPath('data.reviewee.id', $seller->id)
            ->assertJsonPath('data.product.id', $product->id);

        $this->postJson('/api/v1/deals/'.$deal->id.'/review', [
            'rating' => 4,
        ])->assertStatus(409);

        $this->assertDatabaseHas('reviews', [
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
        ]);
        $this->assertSame(1, Review::where('marketplace_deal_id', $deal->id)->count());
    }

    public function test_deal_resource_exposes_buyer_only_review_permissions(): void
    {
        [$seller, $buyer, $product, $deal] = $this->createCompletedDeal();

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/deals/'.$deal->id)
            ->assertOk()
            ->assertJsonPath('data.is_buyer', false)
            ->assertJsonPath('data.buyer_has_reviewed', false)
            ->assertJsonPath('data.actions.can_review', false);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/deals/'.$deal->id)
            ->assertOk()
            ->assertJsonPath('data.is_buyer', true)
            ->assertJsonPath('data.actions.can_review', true);

        Review::create([
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
        ]);

        $this->getJson('/api/v1/deals/'.$deal->id)
            ->assertOk()
            ->assertJsonPath('data.actions.can_review', false)
            ->assertJsonPath('data.buyer_has_reviewed', true);

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/deals/'.$deal->id)
            ->assertOk()
            ->assertJsonPath('data.buyer_has_reviewed', true)
            ->assertJsonPath('data.actions.can_review', false);
    }

    public function test_profile_summary_contains_sales_purchase_balance_and_reviews(): void
    {
        [$seller, $buyer, $product, $deal] = $this->createCompletedDeal();

        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        Review::create([
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 4,
            'comment' => 'Mantap.',
        ]);

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/me/summary')
            ->assertOk()
            ->assertJsonPath('total_sold', 1)
            ->assertJsonPath('total_bought', 0)
            ->assertJsonPath('total_sold_items', 1)
            ->assertJsonPath('total_sales_amount', 90000)
            ->assertJsonPath('total_purchase_amount', 0)
            ->assertJsonPath('balance', 90000)
            ->assertJsonPath('total_reviews', 1)
            ->assertJsonPath('reviews_given', 0)
            ->assertJsonPath('total_products', 1)
            ->assertJsonPath('total_transactions', 1)
            ->assertJsonPath('total_favorites', 0)
            ->assertJsonPath('recent_reviews.0.product.name', $product->name)
            ->assertJsonPath('recent_reviews.0.reviewer.id', $buyer->id);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/me/summary')
            ->assertOk()
            ->assertJsonPath('total_sold', 0)
            ->assertJsonPath('total_bought', 1)
            ->assertJsonPath('total_bought_items', 1)
            ->assertJsonPath('total_sales_amount', 0)
            ->assertJsonPath('total_purchase_amount', 90000)
            ->assertJsonPath('balance', -90000)
            ->assertJsonPath('total_reviews', 0)
            ->assertJsonPath('reviews_given', 1)
            ->assertJsonPath('total_favorites', 1);
    }

    public function test_profile_reviews_endpoint_can_return_received_and_given_reviews(): void
    {
        [$seller, $buyer, $product, $deal] = $this->createCompletedDeal();

        Review::create([
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
            'comment' => 'Ulasan untuk penjual.',
        ]);

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/me/reviews?type=received')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reviewer.id', $buyer->id)
            ->assertJsonPath('data.0.product.name', $product->name);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/me/reviews?type=given')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reviewee.id', $seller->id)
            ->assertJsonPath('data.0.product.name', $product->name);
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: MarketplaceDeal}
     */
    private function createCompletedDeal(): array
    {
        $suffix = Str::lower(Str::random(8));

        $seller = User::create([
            'name' => 'Seller Profile Test',
            'username' => 'seller_profile_'.$suffix,
            'nim' => 'SP'.$suffix,
            'email' => 'seller_profile_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $buyer = User::create([
            'name' => 'Buyer Profile Test',
            'username' => 'buyer_profile_'.$suffix,
            'nim' => 'BP'.$suffix,
            'email' => 'buyer_profile_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Kategori Profile '.$suffix,
            'slug' => 'kategori-profile-'.$suffix,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk Profile '.$suffix,
            'price' => 100000,
            'condition' => 'Bekas',
            'status' => 'sold',
            'verification_status' => 'verified',
        ]);

        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'type' => 'transaction',
        ]);

        $deal = MarketplaceDeal::create([
            'conversation_id' => $conversation->id,
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'agreed_price' => 90000,
            'current_offer_price' => 90000,
            'buyer_agreed' => true,
            'seller_agreed' => true,
            'buyer_agreed_at' => now(),
            'seller_agreed_at' => now(),
            'status' => 'completed',
            'agreed_at' => now(),
            'completed_at' => now(),
        ]);

        return [$seller, $buyer, $product, $deal];
    }
}
