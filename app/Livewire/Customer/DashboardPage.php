<?php

namespace App\Livewire\Customer;

use App\Services\Customer\Dashboard\CustomerDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class DashboardPage extends Component
{
    public array $customer = [];
    public array $credit = [];
    public array $cart = [];
    public array $orders = [];
    public array $recentOrders = [];
    public array $alerts = [];
    public array $quickActions = [];
    public array $recommendedProducts = [];
    public string $lastUpdatedAt = '';

    /**
     * Mount the component and load dashboard data.
     */
    public function mount(CustomerDashboardService $dashboardService)
    {
        $this->loadDashboardData($dashboardService);
    }

    /**
     * Refresh the dashboard data dynamically.
     */
    public function refreshDashboard(CustomerDashboardService $dashboardService)
    {
        $this->loadDashboardData($dashboardService);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dashboard updated successfully.'
        ]);
    }

    /**
     * Helper to populate properties from the service.
     */
    protected function loadDashboardData(CustomerDashboardService $dashboardService)
    {
        $user = Auth::user();
        if (!$user || !$user->customer) {
            return;
        }

        $data = $dashboardService->getDashboard($user);

        if (!empty($data)) {
            $this->customer = $data['customer'];
            $this->credit = $data['credit'];
            $this->cart = $data['cart'];
            $this->orders = $data['orders'];
            $this->recentOrders = $data['recent_orders'];
            $this->alerts = $data['alerts'];
            $this->quickActions = $data['quick_actions'];
            $this->recommendedProducts = $data['recommended_products'];
        }

        $this->lastUpdatedAt = now()->format('h:i A');
    }

    public function render()
    {
        return view('livewire.customer.dashboard-page')
            ->layoutData(['title' => 'Dashboard']);
    }
}
