<?php

namespace App\Services\Checkout;

use App\Models\User;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Cart\CartPricingService;
use App\Services\Order\OrderService;
use App\Services\Payment\ManualPaymentReceiptService;
use App\Services\Credit\CustomerCreditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    protected CartService $cartService;
    protected CartPricingService $cartPricingService;
    protected CreditEligibilityService $creditService;
    protected OrderService $orderService;
    protected ManualPaymentReceiptService $receiptService;
    protected CustomerCreditService $customerCreditService;

    public function __construct(
        CartService $cartService,
        CartPricingService $cartPricingService,
        CreditEligibilityService $creditService,
        OrderService $orderService,
        ManualPaymentReceiptService $receiptService,
        CustomerCreditService $customerCreditService
    ) {
        $this->cartService = $cartService;
        $this->cartPricingService = $cartPricingService;
        $this->creditService = $creditService;
        $this->orderService = $orderService;
        $this->receiptService = $receiptService;
        $this->customerCreditService = $customerCreditService;
    }

    /**
     * Get a full checkout summary for the current user.
     */
    public function getCheckoutSummary(User $user): array
    {
        $cartData = $this->cartService->getCartForCustomer($user);
        $customer = $user->customer;

        $creditEligibility = [
            'can_use_credit' => true,
            'is_within_limit' => true,
            'is_privileged_override' => false,
            'excess_amount' => 0.0,
            'message' => 'Credit check disabled.',
        ];

        return [
            'cart' => $cartData,
            'credit_eligibility' => $creditEligibility,
            'customer' => [
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'credit_limit' => 0.0,
                'available_credit' => 0.0,
                'outstanding_amount' => 0.0,
                'allow_credit_beyond_limit' => false,
                'billing_address' => $customer->billing_address,
            ],
        ];
    }

    /**
     * Submit an order from the active cart.
     */
    public function submitOrder(User $user, array $payload): Order
    {
        return DB::transaction(function () use ($user, $payload) {
            $cart = $this->cartService->getOrCreateActiveCart($user);
            $cart->load('items');

            // Validate cart is not empty
            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty. Add products before checkout.',
                ]);
            }

            // Recalculate cart totals
            $totals = $this->cartPricingService->recalculateCart($cart);

            $method = $payload['checkout_method'] ?? 'regular';

            // Validate checkout method (bypass checks)
            $checkoutEvaluation = $this->validateCheckoutMethod($user, $cart, $method, $payload);

            // Build checkout payload for order service
            $checkoutPayload = [
                'checkout_method' => $method,
                'subtotal' => $totals['subtotal'],
                'gst_amount' => $totals['gst_amount'],
                'total' => $totals['total'],
                'customer_notes' => $payload['customer_notes'] ?? null,
            ];

            // Create order
            $order = $this->orderService->createFromCart($user, $cart, $checkoutPayload, $checkoutEvaluation);

            // Mark cart as converted
            $cart->update(['status' => 'converted']);

            return $order;
        });
    }

    /**
     * Validate the checkout method and return evaluation data.
     */
    public function validateCheckoutMethod(User $user, $cart, string $method, array $payload): array
    {
        return [
            'is_within_limit' => true,
            'is_privileged_override' => false,
        ];
    }
}
