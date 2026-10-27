<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferAcceptTest extends TestCase
{
    use DatabaseTransactions;

    public function test_buyer_can_accept_offer_through_route_model_binding(): void
    {
        $suffix = Str::lower(Str::random(8));

        $seller = User::create([
            'name' => 'Seller Test',
            'username' => 'seller_'.$suffix,
            'nim' => 'S'.$suffix,
            'email' => 'seller_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $buyer = User::create([
            'name' => 'Buyer Test',
            'username' => 'buyer_'.$suffix,
            'nim' => 'B'.$suffix,
            'email' => 'buyer_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Kategori Test '.$suffix,
            'slug' => 'kategori-test-'.$suffix,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk Test '.$suffix,
            'price' => 100000,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);

        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $offer = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $seller->id,
            'type' => 'offer',
            'offer_price' => 90000,
            'offer_status' => 'pending',
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->postJson('/api/v1/offers/'.$offer->id.'/accept', [
            'quantity' => 1,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'agreed');

        $this->assertDatabaseHas('marketplace_deals', [
            'conversation_id' => $conversation->id,
            'status' => 'agreed',
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $offer->id,
            'offer_status' => 'accepted',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'reserved',
        ]);

        $this->assertSame(
            'agreed',
            MarketplaceDeal::where('conversation_id', $conversation->id)->value('status'),
        );
    }
}
