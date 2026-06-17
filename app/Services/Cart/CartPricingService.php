<?php

namespace App\Services\Cart;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Exceptions\ProductTaxConfigurationException;
use App\Services\Portal\CustomerPricingService;
use App\Services\Portal\ProductUnitPricingService;

class CartPricingService
{
    protected CustomerPricingService $pricingService;
    protected ProductUnitPricingService $unitPricingService;

    public function __construct(
        CustomerPricingService $pricingService,
        ProductUnitPricingService $unitPricingService
    ) {
        $this->pricingService = $pricingService;
        $this->unitPricingService = $unitPricingService;
    }

    /**
     * Calculate pricing for a single cart item configuration.
     * GST percentage is always read from the product record — never hardcoded.
     *
     * @throws ProductTaxConfigurationException if product has no GST configured
     */
    public function calculateCartItem(
        User $user,
        Product $product,
        ?ProductCombination $combination,
        ProductUnit $unit,
        int $quantity
    ): array {
        // Guard: GST must be explicitly configured on the product.
        // null means "not configured", which is different from 0 (zero-rated).
        if ($product->gst_percentage === null) {
            throw new ProductTaxConfigurationException(
                "Product \"{$product->title}\" is missing GST configuration and cannot be purchased. Please contact support."
            );
        }

        // Get customer-specific price (applies level/override discounts)
        $pricing = $this->pricingService->calculateCustomerPrice($product, $user, $combination);
        $customerBasePrice = $pricing['customer_price']; // price per base unit (piece)
        $basePrice = $pricing['effective_base_price'];   // price per base unit before discount

        $conversion = $unit->conversion_to_base ? (float) $unit->conversion_to_base : 1.0;

        // customer_unit_price = customer price per piece × conversion factor
        $customerUnitPrice = round($customerBasePrice * $conversion, 2);
        $baseUnitPrice = round($basePrice * $conversion, 2);

        $baseQuantity = (int) ($conversion * $quantity);

        $gstPercentage = (float) $product->gst_percentage;
        $lineSubtotal = round($customerUnitPrice * $quantity, 2);
        $gstAmount = round($lineSubtotal * ($gstPercentage / 100), 2);
        $lineTotal = round($lineSubtotal + $gstAmount, 2);

        return [
            'base_unit_price'          => $baseUnitPrice,
            'customer_unit_price'      => $customerUnitPrice,
            'unit_conversion_quantity' => $conversion,
            'quantity'                 => $quantity,
            'base_quantity'            => $baseQuantity,
            'line_subtotal'            => $lineSubtotal,
            'hsn_code'                 => $product->hsn_code,
            'gst_percentage'           => $gstPercentage,
            'gst_amount'               => $gstAmount,
            'line_total'               => $lineTotal,
            'discount_percentage'      => $pricing['discount_percentage'],
            'discount_source'          => $pricing['discount_source'],
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

            // Skip items whose product no longer has GST configured —
            // they will be blocked at checkout validation instead of silently miscalculating.
            if ($item->product->gst_percentage === null) {
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
                'base_unit_price'          => $pricing['base_unit_price'],
                'customer_unit_price'      => $pricing['customer_unit_price'],
                'unit_conversion_quantity' => $pricing['unit_conversion_quantity'],
                'line_subtotal'            => $pricing['line_subtotal'],
                'hsn_code'                 => $pricing['hsn_code'],
                'gst_percentage'           => $pricing['gst_percentage'],
                'gst_amount'               => $pricing['gst_amount'],
                'line_total'               => $pricing['line_total'],
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
        $gstBreakdown = [];

        foreach ($items as $item) {
            $subtotal     += (float) $item->line_subtotal;
            $gstAmount    += (float) $item->gst_amount;
            $total        += (float) $item->line_total;
            $totalItems++;
            $conversion          = (float) ($item->unit_conversion_quantity ?: 1);
            $totalBaseQuantity  += (int) ($conversion * $item->quantity);

            // Build GST breakdown by rate
            $rate = (float) $item->gst_percentage;
            if (!isset($gstBreakdown[$rate])) {
                $gstBreakdown[$rate] = ['gst_percentage' => $rate, 'taxable_amount' => 0, 'gst_amount' => 0];
            }
            $gstBreakdown[$rate]['taxable_amount'] += (float) $item->line_subtotal;
            $gstBreakdown[$rate]['gst_amount']     += (float) $item->gst_amount;
        }

        // Round breakdown values
        $gstBreakdown = array_values(array_map(function ($row) {
            return [
                'gst_percentage'  => $row['gst_percentage'],
                'taxable_amount'  => round($row['taxable_amount'], 2),
                'gst_amount'      => round($row['gst_amount'], 2),
            ];
        }, $gstBreakdown));

        return [
            'items_count'        => $totalItems,
            'total_base_quantity'=> $totalBaseQuantity,
            'subtotal'           => round($subtotal, 2),
            'gst_amount'         => round($gstAmount, 2),
            'total'              => round($total, 2),
            'gst_breakdown'      => $gstBreakdown,
        ];
    }
}
