<?php

namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class ReportIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.reports.report-index-page');
    }
}
