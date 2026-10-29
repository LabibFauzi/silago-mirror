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

class OfferNegotiationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array{0: User, 1: User, 2: Conversation, 3: MarketplaceDeal, 4: Message}
     */
    private function makeContext(
        string $offerSender = 'buyer',
        string $offerStatus = 'pending',
    ): array {
        $suffix = Str::lower(Str::random(8));

        $seller = User::create([
            'name' => 'Seller Offer',
            'username' => 'seller_offer_'.$suffix,
            'nim' => 'SO'.$suffix,
            'email' => 'seller_offer_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $buyer = User::create([
            'name' => 'Buyer Offer',
            'username' => 'buyer_offer_'.$suffix,
            'nim' => 'BO'.$suffix,
            'email' => 'buyer_offer_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Kategori Offer '.$suffix,
            'slug' => 'kategori-offer-'.$suffix,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk Offer '.$suffix,
            'price' => 100000,
            'status' => 'available',
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
            'agreed_price' => null,
            'current_offer_price' => 90000,
            'status' => 'negotiating',
        ]);

        $offer = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $offerSender === 'buyer' ? $buyer->id : $seller->id,
            'type' => 'offer',
            'offer_price' => 90000,
            'offer_status' => $offerStatus,
        ]);

        return [$seller, $buyer, $conversation, $deal, $offer];
    }

    public function test_sender_can_cancel_own_pending_offer(): void
    {
        [, $buyer, , , $offer] = $this->makeContext();

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/offers/'.$offer->id.'/cancel')->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $offer->id,
            'offer_status' => 'withdrawn',
        ]);
    }

    public function test_other_party_cannot_cancel_offer(): void
    {
        [$seller, , , , $offer] = $this->makeContext();

        Sanctum::actingAs($seller);

        $this->postJson('/api/v1/offers/'.$offer->id.'/cancel')
            ->assertStatus(403);
    }

    public function test_offer_cannot_be_edited(): void
    {
        [, $buyer, , , $offer] = $this->makeContext();

        Sanctum::actingAs($buyer);

        // Route edit sudah dihapus, jadi permintaan tidak lagi dilayani.
        $response = $this->putJson('/api/v1/offers/'.$offer->id, [
            'price' => 95000,
        ]);

        $this->assertTrue(in_array($response->status(), [404, 405], true));
    }

    public function test_seller_can_counter_pending_buyer_offer(): void
    {
        [$seller, $buyer, $conversation, , $offer] = $this->makeContext();

        Sanctum::actingAs($seller);

        $this->postJson('/api/v1/conversations/'.$conversation->id.'/offers', [
            'price' => 95000,
            'parent_offer_id' => $offer->id,
        ])->assertCreated();

        $this->assertDatabaseHas('messages', [
            'id' => $offer->id,
            'offer_status' => 'countered',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'offer',
            'sender_id' => $seller->id,
            'offer_status' => 'pending',
        ]);
    }

    public function test_only_buyer_can_start_new_round_after_rejection(): void
    {
        [$seller, $buyer, $conversation, , $offer] = $this->makeContext(
            offerStatus: 'rejected',
        );

        // Penjual tidak boleh memulai ronde baru.
        Sanctum::actingAs($seller);
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/offers', [
            'price' => 95000,
        ])->assertStatus(403);

        // Pembeli boleh memulai ronde baru.
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/offers', [
            'price' => 95000,
        ])->assertCreated();
    }

    public function test_reject_offer_records_reason_as_system_message(): void
    {
        [$seller, $buyer, $conversation, , $offer] = $this->makeContext();

        Sanctum::actingAs($seller);

        $this->postJson('/api/v1/offers/'.$offer->id.'/reject', [
            'reason' => 'Harga terlalu rendah',
        ])->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $offer->id,
            'offer_status' => 'rejected',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'system',
            'message' => 'Penawaran Rp90.000 ditolak. Alasan: Harga terlalu rendah',
        ]);
    }
}