<?php

namespace App\Services\Cart;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Services\Portal\CustomerPricingService;
use App\Services\Portal\ProductUnitPricingService;

class CartPricingService
{
    protected CustomerPricingService $pricingService;
    protected ProductUnitPricingService $unitPricingService;

    protected const GST_PERCENTAGE = 12.0;

    public function __construct(
        CustomerPricingService $pricingService,
        ProductUnitPricingService $unitPricingService
    ) {
        $this->pricingService = $pricingService;
        $this->unitPricingService = $unitPricingService;
    }

    /**
     * Calculate pricing for a single cart item configuration.
     */
    public function calculateCartItem(
        User $user,
        Product $product,
        ?ProductCombination $combination,
        ProductUnit $unit,
        int $quantity
    ): array {
        // Get customer-specific price (applies level/override discounts)
        $pricing = $this->pricingService->calculateCustomerPrice($product, $user, $combination);
        $customerBasePrice = $pricing['customer_price']; // price per base unit (piece)
        $basePrice = $pricing['effective_base_price']; // price per base unit before discount

        $conversion = $unit->conversion_to_base ? (float) $unit->conversion_to_base : 1.0;

        // customer_unit_price = customer price per piece × conversion factor
        $customerUnitPrice = round($customerBasePrice * $conversion, 2);
        $baseUnitPrice = round($basePrice * $conversion, 2);

        $baseQuantity = (int) ($conversion * $quantity);

        $lineSubtotal = round($customerUnitPrice * $quantity, 2);
        $gstAmount = round($lineSubtotal * (self::GST_PERCENTAGE / 100), 2);
        $lineTotal = round($lineSubtotal + $gstAmount, 2);

        return [
            'base_unit_price' => $baseUnitPrice,
            'customer_unit_price' => $customerUnitPrice,
            'unit_conversion_quantity' => $conversion,
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'line_subtotal' => $lineSubtotal,
            'gst_percentage' => self::GST_PERCENTAGE,
            'gst_amount' => $gstAmount,
            'line_total' => $lineTotal,
            'discount_percentage' => $pricing['discount_percentage'],
            'discount_source' => $pricing['discount_source'],
        ];
    }

    /**
     * Recalculate all items in a cart and update their stored pricing.
     * Returns aggregated totals.
     */
    public function recalculateCart(\App\Models\Cart $cart): array
    {
        $cart->load(['items.product', 'items.combination', 'items.unit']);
        $user = $cart->user;

        foreach ($cart->items as $item) {
            if (!$item->product || !$item->product->is_active) {
                continue;
            }

            $pricing = $this->calculateCartItem(
                $user,
                $item->product,
                $item->combination,
                $item->unit ?? $item->product->units()->where('level', 1)->first(),
                $item->quantity
            );

            $item->update([
                'base_unit_price' => $pricing['base_unit_price'],
                'customer_unit_price' => $pricing['customer_unit_price'],
                'unit_conversion_quantity' => $pricing['unit_conversion_quantity'],
                'line_subtotal' => $pricing['line_subtotal'],
                'gst_percentage' => $pricing['gst_percentage'],
                'gst_amount' => $pricing['gst_amount'],
                'line_total' => $pricing['line_total'],
            ]);
        }

        return $this->calculateTotals($cart->items->fresh());
    }

    /**
     * Aggregate totals from a collection of cart/order items.
     */
    public function calculateTotals($items): array
    {
        $subtotal = 0;
        $gstAmount = 0;
        $total = 0;
        $totalItems = 0;
        $totalBaseQuantity = 0;

        foreach ($items as $item) {
            $subtotal += (float) $item->line_subtotal;
            $gstAmount += (float) $item->gst_amount;
            $total += (float) $item->line_total;
            $totalItems++;
            $conversion = (float) ($item->unit_conversion_quantity ?: 1);
            $totalBaseQuantity += (int) ($conversion * $item->quantity);
        }

        return [
            'items_count' => $totalItems,
            'total_base_quantity' => $totalBaseQuantity,
            'subtotal' => round($subtotal, 2),
            'gst_amount' => round($gstAmount, 2),
            'total' => round($total, 2),
        ];
    }
}
