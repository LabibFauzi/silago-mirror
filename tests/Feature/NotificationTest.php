<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\MarketplaceDeal;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_messages_are_aggregated_and_can_be_marked_read(): void
    {
        [$seller, $buyer, $product, $conversation] = $this->makeConversation();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/messages', [
            'type' => 'text',
            'message' => 'Halo, masih ada?',
        ])->assertCreated();
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/messages', [
            'type' => 'text',
            'message' => 'Saya mau tanya kondisi barangnya.',
        ])->assertCreated();

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'new_message')
            ->assertJsonPath('data.0.is_read', false)
            ->assertJsonPath('data.0.data.count', 2)
            ->assertJsonPath('data.0.reference_id', $conversation->id);

        $this->assertStringContainsString('2 pesan baru dari Buyer Test', (string) Notification::where('user_id', $seller->id)->first()->body);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $notification = Notification::where('user_id', $seller->id)->firstOrFail();
        $this->postJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_chat_presence_suppresses_message_notification(): void
    {
        [$seller, $buyer, $product, $conversation] = $this->makeConversation();

        Sanctum::actingAs($seller);
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/presence')
            ->assertOk();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/conversations/'.$conversation->id.'/messages', [
            'type' => 'text',
            'message' => 'Pesan saat penerima sedang membuka chat.',
        ])->assertCreated();

        $this->assertSame(
            0,
            Notification::where('user_id', $seller->id)->where('type', 'new_message')->count(),
        );
    }

    public function test_offer_deal_completion_and_review_create_notifications(): void
    {
        [$seller, $buyer, $product, $conversation] = $this->makeConversation();

        Sanctum::actingAs($buyer);
        $offerResponse = $this->postJson('/api/v1/conversations/'.$conversation->id.'/offers', [
            'price' => 90000,
        ])->assertCreated();

        $offerId = (int) $offerResponse->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id,
            'type' => 'new_offer',
            'reference_id' => $conversation->id,
        ]);

        Sanctum::actingAs($seller);
        $acceptResponse = $this->postJson('/api/v1/offers/'.$offerId.'/accept')
            ->assertCreated()
            ->assertJsonPath('data.status', 'agreed');

        $dealId = (int) $acceptResponse->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id,
            'type' => 'deal_agreed',
            'reference_id' => $dealId,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->id,
            'type' => 'deal_agreed',
            'reference_id' => $dealId,
        ]);

        $qrResponse = $this->postJson('/api/v1/deals/'.$dealId.'/qr')
            ->assertOk();

        $token = (string) $qrResponse->json('token');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/deals/'.$dealId.'/complete', [
            'token' => $token,
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id,
            'type' => 'deal_completed',
            'reference_id' => $dealId,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->id,
            'type' => 'deal_completed',
            'reference_id' => $dealId,
        ]);

        $this->postJson('/api/v1/deals/'.$dealId.'/review', [
            'rating' => 5,
            'comment' => 'Transaksi lancar.',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id,
            'type' => 'review_received',
            'reference_id' => $dealId,
        ]);
    }

    public function test_old_notifications_and_notifications_of_deleted_chats_are_cleaned(): void
    {
        [$seller, $buyer, $product, $conversation] = $this->makeConversation();

        $old = Notification::create([
            'user_id' => $seller->id,
            'title' => 'Notifikasi lama',
            'body' => 'Harus dibersihkan.',
            'type' => 'info',
        ]);
        \DB::table('notifications')->where('id', $old->id)->update([
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        $chatNotification = Notification::create([
            'user_id' => $seller->id,
            'title' => 'Pesan baru',
            'body' => 'Percakapan akan dihapus.',
            'type' => 'new_message',
            'reference_id' => $conversation->id,
            'data' => ['conversation_id' => $conversation->id],
        ]);

        \DB::table('conversations')->where('id', $conversation->id)->update([
            'updated_at' => now()->subDays(2),
        ]);

        $this->artisan('notifications:purge')->assertSuccessful();
        $this->assertDatabaseMissing('notifications', ['id' => $old->id]);

        $this->artisan('chat:purge')->assertSuccessful();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $chatNotification->id]);
    }

    public function test_review_reminder_is_sent_once_after_one_day(): void
    {
        [$seller, $buyer, $product, $conversation] = $this->makeConversation();

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
            'status' => 'completed',
            'agreed_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2),
        ]);

        $this->artisan('deals:remind-reviews')->assertSuccessful();
        $this->artisan('deals:remind-reviews')->assertSuccessful();

        $this->assertSame(
            1,
            Notification::where('user_id', $buyer->id)
                ->where('type', 'review_reminder')
                ->where('reference_id', $deal->id)
                ->count(),
        );

        Review::create([
            'marketplace_deal_id' => $deal->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
        ]);

        $this->artisan('deals:remind-reviews')->assertSuccessful();

        $this->assertSame(
            1,
            Notification::where('user_id', $buyer->id)
                ->where('type', 'review_reminder')
                ->where('reference_id', $deal->id)
                ->count(),
        );
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: Conversation}
     */
    private function makeConversation(): array
    {
        $suffix = Str::lower(Str::random(8));

        $seller = User::create([
            'name' => 'Seller Test',
            'username' => 'seller_notif_'.$suffix,
            'nim' => 'SN'.$suffix,
            'email' => 'seller_notif_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $buyer = User::create([
            'name' => 'Buyer Test',
            'username' => 'buyer_notif_'.$suffix,
            'nim' => 'BN'.$suffix,
            'email' => 'buyer_notif_'.$suffix.'@example.com',
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Kategori Notif '.$suffix,
            'slug' => 'kategori-notif-'.$suffix,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk Notif '.$suffix,
            'price' => 100000,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);

        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        return [$seller, $buyer, $product, $conversation];
    }
}
