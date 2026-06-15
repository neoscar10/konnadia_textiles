<?php

namespace App\Livewire\Admin\Inventory;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class InventoryIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.inventory.inventory-index-page');
    }
}
