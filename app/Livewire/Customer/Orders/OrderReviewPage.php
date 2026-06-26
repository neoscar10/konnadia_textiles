<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Services\Checkout\CheckoutService;
use Illuminate\Validation\ValidationException;

#[Layout('components.customer.layout')]
class OrderReviewPage extends Component
{
    use WithFileUploads;

    public $items = [];
    public $totals = [];
    public $creditEligibility = [];
    public $customerInfo = [];

    public $checkoutMethod = 'regular';
    public $customerNotes = '';
    public $receiptFile = null;

    public $isSubmitting = false;

    public function mount(CheckoutService $checkoutService)
    {
        $this->loadCheckoutSummary($checkoutService);
    }

    protected function loadCheckoutSummary(CheckoutService $checkoutService)
    {
        $user = auth()->user();
        $summary = $checkoutService->getCheckoutSummary($user);

        $this->items = $summary['cart']['items'];
        $this->totals = $summary['cart']['totals'];
        $this->creditEligibility = $summary['credit_eligibility'];
        $this->customerInfo = $summary['customer'];

        // If cart is empty, redirect to cart page
        if (empty($this->items)) {
            return redirect()->route('customer.cart.index');
        }
    }

    public function selectCheckoutMethod($method)
    {
        // Bypassed
    }

    public function submitOrder(CheckoutService $checkoutService)
    {
        if ($this->isSubmitting) return;

        $this->isSubmitting = true;

        try {
            $payload = [
                'checkout_method' => 'regular',
                'customer_notes' => $this->customerNotes ?: null,
            ];

            $order = $checkoutService->submitOrder(auth()->user(), $payload);

            session()->flash('order_success', [
                'order_number' => $order->order_number,
                'checkout_method' => $order->checkout_method,
                'total_amount' => (float) $order->total_amount,
                'payment_status' => $order->payment_status,
                'credit_status' => $order->credit_status,
                'used_credit_override' => $order->used_credit_override_privilege_privilege ?? false,
            ]);

            return redirect()->route('customer.orders.success');
        } catch (ValidationException $e) {
            $this->isSubmitting = false;
            $firstError = collect($e->errors())->flatten()->first();
            $this->dispatch('toast', type: 'error', message: $firstError);
        } catch (\Exception $e) {
            $this->isSubmitting = false;
            $this->dispatch('toast', type: 'error', message: 'An unexpected error occurred. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.customer.orders.order-review-page')
            ->layoutData(['title' => 'Review Order']);
    }
}
