<?php

namespace App\Livewire\Admin;

use App\Services\Admin\Dashboard\AdminDashboardService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class DashboardPage extends Component
{
    public array $dashboard = [];
    public string $dateRange = '30_days';
    public bool $isLoading = false;

    public function mount(AdminDashboardService $dashboardService): void
    {
        $this->loadDashboard($dashboardService);
    }

    public function updatedDateRange(AdminDashboardService $dashboardService): void
    {
        $this->loadDashboard($dashboardService);
    }

    public function refreshDashboard(AdminDashboardService $dashboardService): void
    {
        $this->isLoading = true;
        $this->loadDashboard($dashboardService);
        $this->isLoading = false;
        $this->dispatch('toast', type: 'success', message: 'Dashboard refreshed successfully.');
    }

    private function loadDashboard(AdminDashboardService $dashboardService): void
    {
        try {
            $this->dashboard = $dashboardService->getDashboard([
                'date_range' => $this->dateRange,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Unable to load dashboard data. Please try again.');
            $this->dashboard = [];
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard-page');
    }
}
