<?php

namespace App\Livewire\Admin\RetailShops;

use App\Models\RetailShop;
use App\Services\RetailShop\RetailShopService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class RetailShopIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $status = '';

    public $editingId = null;
    public $deleteId = null;
    public ?RetailShop $selectedShop = null;

    public $form = [
        'name' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'contact_person' => '',
        'contact_phone' => '',
        'is_active' => true,
    ];

    protected function rules()
    {
        return [
            'form.name' => ['required', 'string', 'max:200'],
            'form.address' => ['required', 'string', 'max:500'],
            'form.city' => ['nullable', 'string', 'max:100'],
            'form.state' => ['nullable', 'string', 'max:100'],
            'form.pincode' => ['nullable', 'string', 'max:20'],
            'form.contact_person' => ['nullable', 'string', 'max:150'],
            'form.contact_phone' => ['nullable', 'string', 'max:30'],
            'form.is_active' => ['boolean'],
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'shop-form');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $shop = RetailShop::findOrFail($id);
        $this->editingId = $shop->id;
        $this->form = [
            'name' => $shop->name,
            'address' => $shop->address,
            'city' => $shop->city,
            'state' => $shop->state,
            'pincode' => $shop->pincode,
            'contact_person' => $shop->contact_person,
            'contact_phone' => $shop->contact_phone,
            'is_active' => $shop->is_active,
        ];
        $this->dispatch('open-modal', 'shop-form');
    }

    public function save(RetailShopService $service)
    {
        $this->validate();

        if ($this->editingId) {
            $shop = RetailShop::findOrFail($this->editingId);
            $service->update($shop, $this->form);
            $this->dispatch('toast', message: 'Retail shop updated successfully.', type: 'success');
        } else {
            $service->create($this->form);
            $this->dispatch('toast', message: 'Retail shop created successfully.', type: 'success');
        }

        $this->dispatch('close-modal', 'shop-form');
        $this->resetForm();
    }

    public function showDetails($id)
    {
        $this->selectedShop = RetailShop::findOrFail($id);
        $this->dispatch('open-modal', 'shop-details');
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('open-modal', 'delete-shop');
    }

    public function delete(RetailShopService $service)
    {
        if ($this->deleteId) {
            $shop = RetailShop::findOrFail($this->deleteId);
            $service->delete($shop);
            $this->dispatch('toast', message: 'Retail shop deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-shop');
            $this->deleteId = null;
        }
    }

    public function toggleStatus($id, RetailShopService $service)
    {
        $shop = RetailShop::findOrFail($id);
        $service->toggleStatus($shop);
        $message = $shop->is_active ? 'Retail shop activated successfully.' : 'Retail shop deactivated successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    private function resetForm()
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'contact_person' => '',
            'contact_phone' => '',
            'is_active' => true,
        ];
    }

    public function render(RetailShopService $service)
    {
        $shops = $service->list([
            'search' => $this->search,
            'status' => $this->status,
        ], 10);

        return view('livewire.admin.retail-shops.retail-shop-index-page', [
            'shops' => $shops,
        ]);
    }
}
