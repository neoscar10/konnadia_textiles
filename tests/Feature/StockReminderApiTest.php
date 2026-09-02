<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductStockReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReminderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_active' => true]);

        $this->product = Product::create([
            'title' => 'Out of Stock Silk Saree',
            'slug' => 'out-of-stock-silk-saree',
            'sku' => 'SAREE-001',
            'stock_quantity' => 0,
            'is_active' => true,
            'gst_percentage' => 5.0,
        ]);

        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Piece',
            'short_code' => 'Pcs',
            'level' => 1,
            'conversion_to_base' => 1.0,
        ]);
    }

    public function test_user_can_subscribe_to_stock_reminder()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/products/{$this->product->id}/stock-reminder", [
                'product_id' => $this->product->id,
                'product_unit_id' => $this->unit->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_stock_reminders', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_list_and_cancel_stock_reminder()
    {
        $reminder = ProductStockReminder::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $listResponse = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stock-reminders');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $reminder->id);

        $cancelResponse = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/stock-reminders/{$reminder->id}");

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_stock_reminders', [
            'id' => $reminder->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_user_can_view_and_update_stock_reminder()
    {
        $reminder = ProductStockReminder::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 2.0,
            'status' => 'pending',
        ]);

        $showResponse = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/stock-reminders/{$reminder->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 2);

        $updateResponse = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/stock-reminders/{$reminder->id}", [
                'quantity' => 15.0,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 15);

        $this->assertDatabaseHas('product_stock_reminders', [
            'id' => $reminder->id,
            'quantity' => 15.0,
        ]);
    }
}
