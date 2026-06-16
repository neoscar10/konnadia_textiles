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

        $creditEligibility = $this->creditService->evaluate($customer, $cartData['totals']['total']);

        return [
            'cart' => $cartData,
            'credit_eligibility' => $creditEligibility,
            'customer' => [
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'credit_limit' => (float) $customer->credit_limit,
                'available_credit' => (float) $customer->available_credit,
                'outstanding_amount' => (float) $customer->outstanding_amount,
                'allow_credit_beyond_limit' => (bool) $customer->allow_credit_beyond_limit,
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

            $method = $payload['checkout_method'];
            $customer = $user->customer;

            // Validate checkout method
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

            // Handle receipt upload for manual payment
            if ($method === 'manual_payment' && isset($payload['receipt_file']) && $payload['receipt_file'] instanceof UploadedFile) {
                $this->receiptService->storeReceipt($order, $payload['receipt_file']);
            }

            // Handle credit balance update
            if ($method === 'credit') {
                $this->customerCreditService->applyCreditOrder($customer, $order);
            }

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
        if (!in_array($method, ['manual_payment', 'credit'])) {
            throw ValidationException::withMessages([
                'checkout_method' => 'Invalid checkout method selected.',
            ]);
        }

        if ($method === 'manual_payment') {
            if (!isset($payload['receipt_file']) || !($payload['receipt_file'] instanceof UploadedFile)) {
                throw ValidationException::withMessages([
                    'receipt_file' => 'Please upload a valid payment receipt.',
                ]);
            }

            $this->receiptService->validateReceiptFile($payload['receipt_file']);

            return [
                'is_within_limit' => false,
                'is_privileged_override' => false,
            ];
        }

        // Credit checkout
        $customer = $user->customer;
        $totals = $this->cartPricingService->recalculateCart($cart);
        $evaluation = $this->creditService->evaluate($customer, $totals['total']);

        if (!$evaluation['can_use_credit']) {
            throw ValidationException::withMessages([
                'checkout_method' => $evaluation['message'],
            ]);
        }

        return $evaluation;
    }
}
