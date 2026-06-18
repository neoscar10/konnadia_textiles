<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Services\Admin\Dashboard\AdminDashboardService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminDashboardService();
    }

    /** @test */
    public function it_gets_dashboard_data()
    {
        Customer::factory()->count(5)->create();
        Product::factory()->count(10)->create();
        Order::factory()->count(3)->create(['status' => 'approved']);

        $dashboard = $this->service->getDashboard();

        $this->assertArrayHasKey('kpis', $dashboard);
        $this->assertArrayHasKey('pending_approvals', $dashboard);
        $this->assertArrayHasKey('recent_customers', $dashboard);
        $this->assertArrayHasKey('recent_orders', $dashboard);
        $this->assertArrayHasKey('metadata', $dashboard);
    }

    /** @test */
    public function it_calculates_kpi_metrics_correctly()
    {
        Customer::factory()->count(10)->create(['is_active' => true]);
        Customer::factory()->count(5)->create(['is_active' => false]);
        Product::factory()->count(15)->create(['is_active' => true]);
        Order::factory()->count(5)->create(['status' => 'pending_approval']);
        Order::factory()->count(3)->create(['status' => 'approved']);

        $kpis = $this->service->getKpiMetrics();

        $this->assertCount(7, $kpis);
        $this->assertEquals('total_customers', $kpis[0]['key']);
        $this->assertEquals(15, $kpis[0]['value']);
        $this->assertEquals('active_customers', $kpis[1]['key']);
        $this->assertEquals(10, $kpis[1]['value']);
        $this->assertEquals('total_products', $kpis[2]['key']);
        $this->assertEquals(15, $kpis[2]['value']);
    }

    /** @test */
    public function it_retrieves_pending_approvals()
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'total_amount' => 50000,
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'under_review',
            'total_amount' => 75000,
        ]);
        Order::factory()->create(['status' => 'approved']);

        $pending = $this->service->getPendingApprovals();

        $this->assertEquals(2, $pending['count']);
        $this->assertCount(2, $pending['orders']);
        $this->assertEquals('submitted', $pending['orders'][0]['status']);
    }

    /** @test */
    public function it_retrieves_recent_customers()
    {
        $customers = Customer::factory()->count(7)->create();

        $recent = $this->service->getRecentCustomers(5);

        $this->assertCount(5, $recent);
        $this->assertArrayHasKey('id', $recent[0]);
        $this->assertArrayHasKey('company_name', $recent[0]);
        $this->assertArrayHasKey('status', $recent[0]);
    }

    /** @test */
    public function it_retrieves_recent_orders()
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(7)->create(['customer_id' => $customer->id]);

        $recent = $this->service->getRecentOrders(5);

        $this->assertCount(5, $recent);
        $this->assertArrayHasKey('order_number', $recent[0]);
        $this->assertArrayHasKey('status_label', $recent[0]);
        $this->assertArrayHasKey('formatted_amount', $recent[0]);
    }

    /** @test */
    public function it_calculates_credit_summary()
    {
        Customer::factory()->create([
            'credit_limit' => 100000,
            'outstanding_amount' => 50000,
        ]);
        Customer::factory()->create([
            'credit_limit' => 200000,
            'outstanding_amount' => 180000,
        ]);

        $credit = $this->service->getCreditSummary();

        $this->assertEquals(300000, $credit['total_credit_limit']);
        $this->assertEquals(230000, $credit['total_outstanding']);
        $this->assertGreaterThan(0, $credit['utilization_percent']);
    }

    /** @test */
    public function it_generates_alerts()
    {
        Customer::factory()->create([
            'credit_limit' => 50000,
            'outstanding_amount' => 60000,
        ]);
        Product::factory()->create(['is_active' => true, 'gst_percentage' => null]);

        $alerts = $this->service->getAlerts();

        $this->assertIsArray($alerts);
        $this->assertGreaterThan(0, count($alerts));
        $this->assertArrayHasKey('type', $alerts[0]);
        $this->assertArrayHasKey('message', $alerts[0]);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5),
            'status' => 'submitted',
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(45),
            'status' => 'submitted',
        ]);

        $pending_30 = $this->service->getPendingApprovals(['date_range' => '30_days']);

        $this->assertEquals(1, $pending_30['count']);
    }

    /** @test */
    public function it_provides_quick_actions()
    {
        $actions = $this->service->getQuickActions();

        $this->assertIsArray($actions);
        $this->assertGreaterThan(0, count($actions));
        $this->assertArrayHasKey('icon', $actions[0]);
        $this->assertArrayHasKey('label', $actions[0]);
        $this->assertArrayHasKey('route', $actions[0]);
    }
}
