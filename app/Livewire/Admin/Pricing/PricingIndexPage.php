<?php

namespace App\Livewire\Admin\Pricing;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class PricingIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.pricing.pricing-index-page');
    }
}
