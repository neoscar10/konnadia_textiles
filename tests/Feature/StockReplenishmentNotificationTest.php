<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\Product;
use App\Models\ProductStockReminder;
use App\Services\Inventory\StockReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReplenishmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_replenishment_notifies_subscribers_and_updates_status()
    {
        $user = User::factory()->create(['is_active' => true, 'mobile_number' => '9876543210']);
        UserDeviceToken::create([
            'user_id' => $user->id,
            'device_token' => 'fcm_test_token_123',
            'platform' => 'android',
        ]);

        $product = Product::create([
            'title' => 'Test Cotton Pillow',
            'slug' => 'test-cotton-pillow',
            'sku' => 'PILLOW-001',
            'stock_quantity' => 0,
            'is_active' => true,
            'gst_percentage' => 5.0,
        ]);

        $reminder = ProductStockReminder::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'phone_number' => '9876543210',
            'status' => 'pending',
        ]);

        $reminderService = app(StockReminderService::class);
        
        // Trigger stock replenishment
        $product->update(['stock_quantity' => 15]);

        $this->assertDatabaseHas('product_stock_reminders', [
            'id' => $reminder->id,
            'status' => 'notified',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }
}
