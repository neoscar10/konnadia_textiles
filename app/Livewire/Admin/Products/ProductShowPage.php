<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\CustomerLevel;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class ProductShowPage extends Component
{
    #[Url(as: 'id')]
    public ?int $productId = null;

    public ?Product $product = null;

    public function mount()
    {
        if ($this->productId) {
            $this->product = Product::with([
                'categories',
                'media',
                'variationGroups.values.media',
                'combinations',
                'customerLevelPrices.customerLevel',
                'units'
            ])->findOrFail($this->productId);
        }
    }

    public function render()
    {
        $customerLevels = CustomerLevel::active()->ordered()->get();

        return view('livewire.admin.products.product-show-page', [
            'customerLevels' => $customerLevels,
        ]);
    }
}
