<?php

namespace App\Services\Portal;

use App\Models\Product;
use App\Models\ProductUnit;

class ProductUnitPricingService
{
    /**
     * Get units list with calculated price labels.
     */
    public function getAvailableUnits(Product $product, float $customerUnitPrice = 0.0): array
    {
        $units = $product->units;
        $result = [];

        foreach ($units as $unit) {
            $price = $this->calculatePriceForUnit($customerUnitPrice, $unit);
            
            $label = $unit->level === 1 
                ? $unit->name 
                : "{$unit->name} (" . round($unit->conversion_to_base) . " " . ($units->where('level', 1)->first()->short_code ?? 'Pcs') . ")";

            $result[] = [
                'id' => $unit->id,
                'level' => $unit->level,
                'name' => $unit->name,
                'short_code' => $unit->short_code,
                'conversion_to_base' => (float) $unit->conversion_to_base,
                'price' => $price,
                'label' => $label,
            ];
        }

        return $result;
    }

    /**
     * Calculate price of a specific unit using base unit customer price.
     */
    public function calculatePriceForUnit(float $customerUnitPrice, ProductUnit $unit): float
    {
        $conversion = $unit->conversion_to_base ? (float) $unit->conversion_to_base : 1.0;
        return round($customerUnitPrice * $conversion, 2);
    }

    /**
     * Calculate line estimates including subtotal, GST, and totals.
     */
    public function calculateLineEstimate(float $customerUnitPrice, ProductUnit $unit, int $quantity, float $gstPercentage = 12.0): array
    {
        $unitPrice = $this->calculatePriceForUnit($customerUnitPrice, $unit);
        $subtotal = $unitPrice * $quantity;
        $gstAmount = $subtotal * ($gstPercentage / 100);
        $total = $subtotal + $gstAmount;

        return [
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'subtotal' => round($subtotal, 2),
            'gst_percentage' => $gstPercentage,
            'gst_amount' => round($gstAmount, 2),
            'total' => round($total, 2),
        ];
    }
}
