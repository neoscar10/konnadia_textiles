<?php

namespace App\Support;

class MoneyFormatter
{
    /**
     * Format amount as full INR currency string.
     * Example: 1500000 becomes ₹15,00,000
     */
    public static function inr(float|int $amount): string
    {
        $amount = (float) $amount;
        
        if ($amount < 0) {
            return '-' . self::inr(abs($amount));
        }

        // Split into integer and decimal parts
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $integerPart = $parts[0];
        $decimalPart = $parts[1] ?? '00';

        // Convert integer part to Indian format with commas
        if (strlen($integerPart) <= 3) {
            $formatted = $integerPart;
        } else {
            $lastThree = substr($integerPart, -3);
            $remaining = substr($integerPart, 0, -3);
            // Add commas every 2 digits from the right for the remaining part
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $formatted = $remaining . ',' . $lastThree;
        }

        return '₹' . $formatted . '.' . $decimalPart;
    }

    /**
     * Format amount as compact INR notation.
     * Example: 1500000 becomes ₹15L, 150000000 becomes ₹1.5Cr
     */
    public static function compactInr(float|int $amount): string
    {
        $amount = (float) $amount;

        if ($amount < 0) {
            return '-' . self::compactInr(abs($amount));
        }

        if ($amount >= 10000000) {
            return '₹' . number_format($amount / 10000000, 2, '.', '') . 'Cr';
        } elseif ($amount >= 100000) {
            return '₹' . number_format($amount / 100000, 2, '.', '') . 'L';
        } elseif ($amount >= 1000) {
            return '₹' . number_format($amount / 1000, 2, '.', '') . 'K';
        } else {
            return '₹' . number_format($amount, 2, '.', '');
        }
    }

    /**
     * Get displayable percentage with proper formatting.
     * Example: 12.5 becomes +12.5%, -5 becomes -5%
     */
    public static function percentage(float $value, int $decimals = 1): string
    {
        $sign = $value >= 0 ? '+' : '';
        return $sign . number_format($value, $decimals) . '%';
    }
}
