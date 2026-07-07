<?php

namespace App\Livewire\Customer\Cart;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Validation\ValidationException;

#[Layout('components.customer.layout')]
class CartPage extends Component
{
    public $items = [];
    public $totals = [];
    public $creditSummary = [];
    public $isEmpty = true;
    public bool $showClearCartConfirmModal = false;

    public function mount(CartService $cartService)
    {
        $this->loadCart($cartService);
    }

    #[On('cart-updated')]
    public function refreshCart(CartService $cartService)
    {
        $this->loadCart($cartService);
    }

    protected function loadCart(CartService $cartService)
    {
        $user = auth()->user();
        $cartData = $cartService->getCartForCustomer($user);
        
        $this->items = $cartData['items'];
        $this->totals = $cartData['totals'];
        $this->isEmpty = empty($cartData['items']);

        $customer = $user->customer;
        $this->creditSummary = [
            'credit_limit' => (float) $customer->credit_limit,
            'available_credit' => (float) $customer->available_credit,
            'outstanding_amount' => (float) $customer->outstanding_amount,
        ];
    }

    public function updateItemQty($itemId, $qtyLvl1, $qtyLvl2, CartService $cartService)
    {
        $item = CartItem::find($itemId);

        if (!$item) {
            $this->dispatch('toast', type: 'error', message: 'Cart item not found.');
            return;
        }

        $qtyLvl1 = max(0, (int) $qtyLvl1);
        $qtyLvl2 = max(0, (int) $qtyLvl2);

        $unit = $item->unit;
        if ($qtyLvl1 === 0 && $qtyLvl2 === 0) {
            if ($unit && $unit->level === 2) {
                $qtyLvl2 = 1;
            } else {
                $qtyLvl1 = 1;
            }
        }

        try {
            if ($unit && $unit->level === 2) {
                $payload = [
                    'quantity' => $qtyLvl2,
                ];
            } else {
                $payload = [
                    'quantity' => $qtyLvl1,
                ];
            }

            $cartService->updateItem(auth()->user(), $item, $payload);
            $this->loadCart($cartService);
            $this->dispatch('toast', type: 'success', message: 'Cart updated successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function updateQtyLvl1($itemId, $qty, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, $qty, $item->quantity_lvl2, $cartService);
        }
    }

    public function updateQtyLvl2($itemId, $qty, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, $item->quantity_lvl1, $qty, $cartService);
        }
    }

    public function updateQuantity($itemId, $qty, CartService $cartService)
    {
        $this->updateQtyLvl1($itemId, $qty, $cartService);
    }

    public function incrementQtyLvl1($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, $item->quantity_lvl1 + 1, $item->quantity_lvl2, $cartService);
        }
    }

    public function decrementQtyLvl1($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, max(0, $item->quantity_lvl1 - 1), $item->quantity_lvl2, $cartService);
        }
    }

    public function incrementQtyLvl2($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, $item->quantity_lvl1, $item->quantity_lvl2 + 1, $cartService);
        }
    }

    public function decrementQtyLvl2($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateItemQty($itemId, $item->quantity_lvl1, max(0, $item->quantity_lvl2 - 1), $cartService);
        }
    }

    public function removeItem($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);

        if (!$item) {
            $this->dispatch('toast', type: 'error', message: 'Cart item not found.');
            return;
        }

        try {
            $cartService->removeItem(auth()->user(), $item);
            $this->loadCart($cartService);
            $this->dispatch('toast', type: 'success', message: 'Item removed from cart successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function clearCart(CartService $cartService)
    {
        $cartService->clearCart(auth()->user());
        $this->showClearCartConfirmModal = false;
        $this->loadCart($cartService);
        $this->dispatch('toast', type: 'success', message: 'Cart cleared successfully.');
        $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
    }

    public function render()
    {
        return view('livewire.customer.cart.cart-page')
            ->layoutData(['title' => 'My Cart']);
    }
}
