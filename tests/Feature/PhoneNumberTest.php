<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    use DatabaseTransactions;

    public function test_phone_can_be_saved_without_otp_and_visibility_controls_exposure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me/phone', ['phone' => '081234567890'])
            ->assertOk()
            ->assertJsonPath('user.phone', '6281234567890')
            ->assertJsonPath('user.has_phone', true)
            ->assertJsonPath('user.show_phone', true);

        // Pemilik selalu melihat nomornya sendiri.
        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.phone', '6281234567890')
            ->assertJsonPath('user.has_phone', true);

        $other = User::factory()->create();
        Sanctum::actingAs($other);

        // Nomor pertama langsung tampil ke orang lain.
        $this->getJson('/api/v1/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('phone', '6281234567890');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/me/phone/visibility', ['show_phone' => false])
            ->assertOk()
            ->assertJsonPath('user.show_phone', false);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('phone', null);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/me/phone/visibility', ['show_phone' => true])
            ->assertOk()
            ->assertJsonPath('user.show_phone', true);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('phone', '6281234567890');
    }

    public function test_phone_must_exist_before_it_can_be_shown(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/phone/visibility', ['show_phone' => true])
            ->assertStatus(422);
    }

    public function test_phone_can_be_removed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Nomor unik agar tidak bentrok dengan data yang sudah ada di DB.
        do {
            $suffix = (string) random_int(10000000, 99999999);
            $normalized = '6281'.$suffix;
        } while (User::where('phone', $normalized)->exists());

        $raw = '+62 81-'.substr($suffix, 0, 4).'-'.substr($suffix, 4);

        $this->putJson('/api/v1/me/phone', ['phone' => $raw])
            ->assertOk()
            ->assertJsonPath('user.phone', $normalized);

        $this->deleteJson('/api/v1/me/phone')->assertOk();

        $user->refresh();
        $this->assertNull($user->phone);
        $this->assertFalse((bool) $user->show_phone);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me/phone', ['phone' => '123'])
            ->assertStatus(422);
    }

    public function test_product_detail_shows_seller_phone_only_when_visible(): void
    {
        $seller = User::factory()->create([
            'phone' => '6281234567890',
            'show_phone' => true,
        ]);
        $buyer = User::factory()->create();

        $suffix = uniqid();
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

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.seller.phone', '6281234567890')
            ->assertJsonPath('data.seller.has_phone', true);

        // Nomor disembunyikan lagi: pembeli tidak boleh melihatnya.
        $seller->update(['show_phone' => false]);

        $this->getJson('/api/v1/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.seller.phone', null)
            ->assertJsonPath('data.seller.has_phone', true);
    }

    public function test_chat_other_party_phone_is_available_for_three_dot_menu(): void
    {
        $seller = User::factory()->create([
            'phone' => '6281234567890',
            'show_phone' => true,
        ]);
        $buyer = User::factory()->create();

        $suffix = uniqid();
        $category = Category::create([
            'name' => 'Kategori Chat '.$suffix,
            'slug' => 'kategori-chat-'.$suffix,
        ]);
        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Produk Chat '.$suffix,
            'price' => 100000,
            'status' => 'available',
            'verification_status' => 'verified',
        ]);
        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath(
                'data.conversation.other_party.phone',
                '6281234567890',
            )
            ->assertJsonPath('data.conversation.other_party.has_phone', true);

        $seller->update(['show_phone' => false]);

        $this->getJson('/api/v1/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath('data.conversation.other_party.phone', null)
            ->assertJsonPath('data.conversation.other_party.has_phone', true);
    }
}