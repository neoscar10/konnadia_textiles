<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_can_register_fcm_device_token()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/user/device-tokens', [
                'device_token' => 'sample_fcm_token_abc123',
                'platform'     => 'android',
                'device_name'  => 'Samsung Galaxy S23',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device_token', 'sample_fcm_token_abc123');

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id'      => $this->user->id,
            'device_token' => 'sample_fcm_token_abc123',
            'platform'     => 'android',
        ]);
    }

    public function test_can_list_and_delete_device_token()
    {
        UserDeviceToken::create([
            'user_id'      => $this->user->id,
            'device_token' => 'token_to_remove',
            'platform'     => 'ios',
        ]);

        $listResponse = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/user/device-tokens');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.device_token', 'token_to_remove');

        $deleteResponse = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/v1/user/device-tokens', [
                'device_token' => 'token_to_remove',
            ]);

        $deleteResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('user_device_tokens', [
            'device_token' => 'token_to_remove',
        ]);
    }
}
