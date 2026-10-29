<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Nama Lama']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me', ['name' => 'Nama Baru'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nama Baru');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/me', [
            '_method' => 'PUT',
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_user_can_remove_profile_photo(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('profile-photos/old.jpg', 'dummy');

        $user = User::factory()->create(['profile_photo' => 'profile-photos/old.jpg']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me', ['remove_profile_photo' => true])
            ->assertOk()
            ->assertJsonPath('user.profile_photo_url', null);

        $user->refresh();
        $this->assertNull($user->profile_photo);
        Storage::disk('public')->assertMissing('profile-photos/old.jpg');
    }
}
