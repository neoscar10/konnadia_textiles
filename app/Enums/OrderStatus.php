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
    case PARTIALLY_DISPATCHED = 'partially_dispatched';
    case PARTIALLY_DISPATCHED_BALANCE_CANCELLED = 'partially_dispatched_balance_cancelled';
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
            self::PARTIALLY_DISPATCHED => 'Partially Dispatched',
            self::PARTIALLY_DISPATCHED_BALANCE_CANCELLED => 'Partially Dispatched, Balance Cancelled',
            self::DISPATCHED => 'Dispatched',
            self::CANCELLED => 'Cancelled',
        };
    }
}
