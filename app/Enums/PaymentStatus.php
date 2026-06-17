<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING_VERIFICATION = 'pending_verification';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::NOT_REQUIRED => 'Not Required',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
        };
    }
}
