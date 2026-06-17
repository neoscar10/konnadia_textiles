<?php

namespace App\Enums;

enum CheckoutMethod: string
{
    case MANUAL_PAYMENT = 'manual_payment';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match($this) {
            self::MANUAL_PAYMENT => 'Manual Payment',
            self::CREDIT => 'Credit Purchase',
        };
    }
}
