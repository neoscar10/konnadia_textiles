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

    public function updateQuantity($itemId, $qty, CartService $cartService)
    {
        $qty = max(1, (int) $qty);
        $item = CartItem::find($itemId);

        if (!$item) {
            $this->dispatch('toast', type: 'error', message: 'Cart item not found.');
            return;
        }

        try {
            $payload = ['quantity' => $qty];
            $product = $item->product;
            $lvl2Unit = $product ? $product->units()->where('level', 2)->first() : null;
            if ($lvl2Unit) {
                $conversion = (int) $lvl2Unit->conversion_to_base;
                $payload['quantity_lvl1'] = $qty % $conversion;
                $payload['quantity_lvl2'] = floor($qty / $conversion);
            }

            $cartService->updateItem(auth()->user(), $item, $payload);
            $this->loadCart($cartService);
            $this->dispatch('toast', type: 'success', message: 'Cart updated successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function updateQuantityLvl1($itemId, $val, CartService $cartService)
    {
        $val = max(0, (int)$val);
        $item = CartItem::find($itemId);
        if (!$item) return;

        try {
            $cartService->updateItem(auth()->user(), $item, [
                'quantity_lvl1' => $val,
                'quantity_lvl2' => $item->quantity_lvl2,
            ]);
            $this->loadCart($cartService);
            $this->dispatch('toast', type: 'success', message: 'Cart updated successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function updateQuantityLvl2($itemId, $val, CartService $cartService)
    {
        $val = max(0, (int)$val);
        $item = CartItem::find($itemId);
        if (!$item) return;

        try {
            $cartService->updateItem(auth()->user(), $item, [
                'quantity_lvl1' => $item->quantity_lvl1,
                'quantity_lvl2' => $val,
            ]);
            $this->loadCart($cartService);
            $this->dispatch('toast', type: 'success', message: 'Cart updated successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount(auth()->user()));
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function incrementQuantity($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item) {
            $this->updateQuantity($itemId, $item->quantity + 1, $cartService);
        }
    }

    public function decrementQuantity($itemId, CartService $cartService)
    {
        $item = CartItem::find($itemId);
        if ($item && $item->quantity > 1) {
            $this->updateQuantity($itemId, $item->quantity - 1, $cartService);
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
