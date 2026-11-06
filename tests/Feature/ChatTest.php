<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private User $buyer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['status' => 'active']);
        $this->buyer = User::factory()->create(['status' => 'active']);

        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
        ]);

        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'name' => 'Sepatu New Balance',
            'price' => 100000,
            'stock' => 2,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);
    }

    public function test_buyer_can_create_conversation_and_send_message(): void
    {
        $this->actingAs($this->buyer, 'sanctum');

        $create = $this->postJson("/api/v1/products/{$this->product->id}/conversations")
            ->assertOk();

        $conversationId = $create->json('data.id');
        $this->assertNotNull($conversationId);

        $this->postJson("/api/v1/conversations/{$conversationId}/messages", [
            'type' => 'text',
            'message' => 'Masih tersedia, Kak?',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.message', 'Masih tersedia, Kak?');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $this->buyer->id,
            'type' => 'text',
        ]);

        // Notifikasi untuk lawan bicara harus tersimpan tanpa error.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'pesan_baru',
            'reference_id' => $conversationId,
        ]);

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.role', 'buyer')
            ->assertJsonPath('data.0.stage', 'chat')
            ->assertJsonPath('data.0.unread_count', 0)
            ->assertJsonPath('data.0.last_message.sender_id', $this->buyer->id);
    }

    public function test_seller_cannot_start_conversation_for_own_product(): void
    {
        $this->actingAs($this->seller, 'sanctum');

        $this->postJson("/api/v1/products/{$this->product->id}/conversations")
            ->assertForbidden();
    }

    public function test_message_history_returns_latest_messages_in_ascending_order(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        for ($i = 1; $i <= 40; $i++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->buyer->id,
                'type' => 'text',
                'message' => "Pesan {$i}",
            ]);
        }

        $this->actingAs($this->buyer, 'sanctum');

        $response = $this->getJson("/api/v1/conversations/{$conversation->id}/messages?limit=10")
            ->assertOk();

        $this->assertCount(10, $response->json('data'));
        $this->assertSame('Pesan 31', $response->json('data.0.message'));
        $this->assertSame('Pesan 40', $response->json('data.9.message'));
    }

    public function test_offer_acceptance_creates_deal_and_system_message(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $this->actingAs($this->buyer, 'sanctum');

        $offerResponse = $this->postJson("/api/v1/conversations/{$conversation->id}/offers", [
            'price' => 90000,
            'note' => 'Bisa nego tipis, Kak?',
        ])->assertCreated();

        $offerId = $offerResponse->json('data.id');
        $this->assertDatabaseHas('messages', [
            'id' => $offerId,
            'message' => 'Bisa nego tipis, Kak?',
            'offer_price' => 90000,
            'offer_status' => 'pending',
        ]);

        $this->actingAs($this->seller, 'sanctum');

        $dealResponse = $this->postJson("/api/v1/offers/{$offerId}/accept")
            ->assertCreated()
            ->assertJsonPath('data.status', 'agreed')
            ->assertJsonPath('data.agreed_price', '90000.00');

        $dealId = $dealResponse->json('data.id');

        $this->assertDatabaseHas('marketplace_deals', [
            'id' => $dealId,
            'conversation_id' => $conversation->id,
            'status' => 'agreed',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'system',
            'sender_id' => $this->seller->id,
        ]);

        $this->assertSame(1, $this->product->fresh()->stock);

        $this->actingAs($this->buyer, 'sanctum');

        $this->getJson("/api/v1/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.conversation.stage', 'deal')
            ->assertJsonPath('data.conversation.role', 'buyer')
            ->assertJsonPath('data.last_offer.id', $offerId)
            ->assertJsonPath('data.last_offer.offer_status', 'accepted')
            ->assertJsonPath('data.latest_deal.id', $dealId)
            ->assertJsonPath('data.latest_deal.status', 'agreed')
            ->assertJsonPath('data.latest_deal.actions.can_complete', true)
            ->assertJsonPath('data.latest_deal.actions.can_generate_qr', false);
    }

    public function test_seller_can_generate_qr_and_buyer_can_complete_deal(): void
    {
        [$conversation, $deal] = $this->createAgreedDeal();

        $this->actingAs($this->seller, 'sanctum');

        $qr = $this->postJson("/api/v1/deals/{$deal->id}/qr")
            ->assertOk()
            ->assertJsonStructure(['token', 'code', 'expires_at', 'qr_data']);

        $token = $qr->json('token');

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson("/api/v1/deals/{$deal->id}/complete", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('marketplace_deals', [
            'id' => $deal->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'system',
            'sender_id' => $this->buyer->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'transaksi_selesai',
        ]);
    }

    public function test_conversation_list_reports_unread_and_role(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'type' => 'text',
            'message' => 'Halo, barang masih ada.',
        ]);

        $this->actingAs($this->buyer, 'sanctum');

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.role', 'buyer')
            ->assertJsonPath('data.0.unread_count', 1);

        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->actingAs($this->seller, 'sanctum');

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.role', 'seller')
            ->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_conversation_stage_uses_pending_offer_even_after_system_message(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'type' => 'offer',
            'offer_price' => 90000,
            'offer_status' => 'pending',
        ]);

        // System message punya id lebih besar; latestOffer harus tetap menemukan offer.
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'type' => 'system',
            'message' => 'Penawaran dibuat.',
        ]);

        $this->actingAs($this->seller, 'sanctum');

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.stage', 'negotiation')
            ->assertJsonPath('data.0.last_message.type', 'system');

        $this->getJson("/api/v1/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.conversation.stage', 'negotiation')
            ->assertJsonPath('data.last_offer.offer_status', 'pending');
    }

    public function test_cannot_create_new_offer_while_deal_is_active(): void
    {
        [$conversation] = $this->createAgreedDeal();

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson("/api/v1/conversations/{$conversation->id}/offers", [
            'price' => 80000,
        ])->assertStatus(409);
    }

    public function test_cannot_create_offer_after_deal_completed(): void
    {
        [$conversation, $deal] = $this->createAgreedDeal();
        $deal->update(['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson("/api/v1/conversations/{$conversation->id}/offers", [
            'price' => 80000,
        ])->assertStatus(409);
    }

    public function test_sender_can_withdraw_pending_offer(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $this->actingAs($this->buyer, 'sanctum');

        $offerId = $this->postJson("/api/v1/conversations/{$conversation->id}/offers", [
            'price' => 90000,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/offers/{$offerId}/withdraw")
            ->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $offerId,
            'offer_status' => 'rejected',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'system',
            'message' => 'Penawaran ditarik.',
        ]);
    }

    public function test_mark_read_clears_unread_count(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'type' => 'text',
            'message' => 'Halo.',
        ]);

        $this->actingAs($this->buyer, 'sanctum');

        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->postJson("/api/v1/conversations/{$conversation->id}/read")
            ->assertOk();

        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_mark_all_read_clears_every_unread_message(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        foreach (range(1, 2) as $index) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->seller->id,
                'type' => 'text',
                'message' => "Pesan {$index}",
            ]);
        }

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson('/api/v1/conversations/read-all')
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->getJson('/api/v1/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_cancel_deal_restores_stock_and_creates_system_message(): void
    {
        [$conversation, $deal] = $this->createAgreedDeal();
        $this->product->update(['stock' => 1, 'status' => 'available']);

        $this->actingAs($this->seller, 'sanctum');

        $this->postJson("/api/v1/deals/{$deal->id}/cancel", [
            'note' => 'Pembeli berubah pikiran.',
        ])->assertOk();

        $this->assertDatabaseHas('marketplace_deals', [
            'id' => $deal->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->seller->id,
        ]);

        $this->assertSame(2, $this->product->fresh()->stock);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'system',
            'message' => 'Transaksi dibatalkan. Alasan: Pembeli berubah pikiran.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->buyer->id,
            'type' => 'transaksi_dibatalkan',
        ]);
    }

    public function test_completed_deal_can_be_reviewed_once(): void
    {
        [$conversation, $deal] = $this->createAgreedDeal();
        $deal->update(['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson("/api/v1/deals/{$deal->id}/review", [
            'rating' => 5,
            'comment' => 'Barang sesuai deskripsi.',
        ])->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $this->buyer->id,
            'reviewee_id' => $this->seller->id,
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'ulasan_baru',
            'reference_id' => $deal->id,
        ]);

        $this->postJson("/api/v1/deals/{$deal->id}/review", [
            'rating' => 4,
        ])->assertStatus(409);

        $this->getJson("/api/v1/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.latest_deal.actions.can_review', false);
    }

    public function test_non_participant_cannot_read_conversation(): void
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $outsider = User::factory()->create(['status' => 'active']);
        $this->actingAs($outsider, 'sanctum');

        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertForbidden();

        $this->getJson("/api/v1/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    /**
     * @return array{0: Conversation, 1: MarketplaceDeal}
     */
    private function createAgreedDeal(): array
    {
        $conversation = Conversation::create([
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $offer = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'type' => 'offer',
            'offer_price' => 90000,
            'offer_status' => 'pending',
        ]);

        $deal = MarketplaceDeal::create([
            'conversation_id' => $conversation->id,
            'accepted_message_id' => $offer->id,
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'quantity' => 1,
            'agreed_price' => 90000,
            'status' => 'agreed',
            'agreed_at' => now(),
        ]);

        return [$conversation, $deal];
    }
}
