<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerWebProductStockFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $inStockProduct;
    protected Product $lowStockProduct;
    protected Product $outOfStockProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $level = CustomerLevel::create([
            'name' => 'Gold Level',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-WEB-001',
            'customer_level_id' => $level->id,
            'company_name' => 'Web Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Web Person',
            'mobile_number' => '9000000001',
            'email' => $this->user->email,
            'is_active' => true,
        ]);

        $this->inStockProduct = Product::create([
            'title' => 'In Stock Fabric',
            'sku' => 'SKU-WEB-IN',
            'base_price' => 1000,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $this->lowStockProduct = Product::create([
            'title' => 'Low Stock Fabric',
            'sku' => 'SKU-WEB-LOW',
            'base_price' => 1500,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->outOfStockProduct = Product::create([
            'title' => 'Out of Stock Fabric',
            'sku' => 'SKU-WEB-OUT',
            'base_price' => 2000,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
    }

    public function test_customer_web_catalog_filters_by_availability_and_low_stock()
    {
        // Out of stock filter
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Customer\Products\ProductIndexPage::class)
            ->set('availability', 'out_of_stock')
            ->assertSee($this->outOfStockProduct->title)
            ->assertDontSee($this->inStockProduct->title)
            ->assertDontSee($this->lowStockProduct->title);

        // Low stock filter
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Customer\Products\ProductIndexPage::class)
            ->set('availability', 'low_stock')
            ->assertSee($this->lowStockProduct->title)
            ->assertDontSee($this->inStockProduct->title)
            ->assertDontSee($this->outOfStockProduct->title);

        // In stock filter
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Customer\Products\ProductIndexPage::class)
            ->set('availability', 'in_stock')
            ->assertSee($this->inStockProduct->title)
            ->assertDontSee($this->outOfStockProduct->title);
    }
}
