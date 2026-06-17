<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case PENDING_APPROVAL = 'pending_approval';
    case PENDING_PAYMENT_VERIFICATION = 'pending_payment_verification';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DISPATCHED = 'dispatched';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::PENDING_PAYMENT_VERIFICATION => 'Pending Payment Verification',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::DISPATCHED => 'Dispatched',
            self::CANCELLED => 'Cancelled',
        };
    }
}
