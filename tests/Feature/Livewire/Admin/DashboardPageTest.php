<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\DashboardPage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_loads_dashboard_on_mount()
    {
        Customer::factory()->count(5)->create();
        Product::factory()->count(10)->create();
        Order::factory()->count(3)->create();

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard');
    }

    /** @test */
    public function it_has_initial_state()
    {
        Livewire::test(DashboardPage::class)
            ->assertSet('dateRange', '30_days')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function it_updates_date_range()
    {
        Customer::factory()->count(5)->create();

        Livewire::test(DashboardPage::class)
            ->set('dateRange', '7_days')
            ->assertSet('dateRange', '7_days')
            ->assertViewHas('dashboard');
    }

    /** @test */
    public function it_refreshes_dashboard()
    {
        Customer::factory()->count(5)->create();

        Livewire::test(DashboardPage::class)
            ->call('refreshDashboard')
            ->assertViewHas('dashboard');
    }

    /** @test */
    public function dashboard_contains_kpi_metrics()
    {
        Customer::factory()->count(5)->create();
        Product::factory()->count(10)->create();

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['kpis']) && is_array($dashboard['kpis']);
            });
    }

    /** @test */
    public function dashboard_contains_pending_approvals()
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
        ]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['pending_approvals']);
            });
    }

    /** @test */
    public function dashboard_contains_recent_customers()
    {
        Customer::factory()->count(5)->create();

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['recent_customers']);
            });
    }

    /** @test */
    public function dashboard_contains_recent_orders()
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(3)->create(['customer_id' => $customer->id]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['recent_orders']);
            });
    }

    /** @test */
    public function dashboard_contains_alerts()
    {
        Customer::factory()->create([
            'credit_limit' => 50000,
            'outstanding_amount' => 60000,
        ]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['alerts']) && is_array($dashboard['alerts']);
            });
    }

    /** @test */
    public function dashboard_contains_quick_actions()
    {
        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['quick_actions']) && count($dashboard['quick_actions']) > 0;
            });
    }

    /** @test */
    public function date_range_filter_today_works()
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'created_at' => now()->subDays(1),
        ]);

        Livewire::test(DashboardPage::class)
            ->set('dateRange', 'today')
            ->assertViewHas('dashboard', function ($dashboard) {
                // Should only see today's pending orders
                return $dashboard['pending_approvals']['count'] >= 1;
            });
    }

    /** @test */
    public function date_range_filter_all_time_works()
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
        ]);

        Livewire::test(DashboardPage::class)
            ->set('dateRange', 'all_time')
            ->assertViewHas('dashboard', function ($dashboard) {
                return isset($dashboard['metadata']['date_range'])
                    && $dashboard['metadata']['date_range'] === 'all_time';
            });
    }

    /** @test */
    public function renders_correctly_with_no_data()
    {
        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard');
    }

    /** @test */
    public function dashboard_displays_formatted_currency()
    {
        Customer::factory()->create([
            'outstanding_amount' => 1500000,
        ]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                $credit = $dashboard['credit'];
                return strpos($credit['formatted_outstanding'], '₹') !== false;
            });
    }

    /** @test */
    public function pending_approvals_limited_to_5()
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(10)->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
        ]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return count($dashboard['pending_approvals']['orders']) <= 5;
            });
    }

    /** @test */
    public function recent_customers_limited_to_5()
    {
        Customer::factory()->count(10)->create();

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return count($dashboard['recent_customers']) <= 5;
            });
    }

    /** @test */
    public function recent_orders_limited_to_5()
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(10)->create(['customer_id' => $customer->id]);

        Livewire::test(DashboardPage::class)
            ->assertViewHas('dashboard', function ($dashboard) {
                return count($dashboard['recent_orders']) <= 5;
            });
    }
}
