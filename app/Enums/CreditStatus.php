<?php

namespace App\Enums;

enum CreditStatus: string
{
    case WITHIN_LIMIT = 'within_limit';
    case OVER_LIMIT_ALLOWED = 'over_limit_allowed';
    case OVER_LIMIT_BLOCKED = 'over_limit_blocked';
    case PENDING_REVIEW = 'pending_review';

    public function label(): string
    {
        return match($this) {
            self::WITHIN_LIMIT => 'Within Limit',
            self::OVER_LIMIT_ALLOWED => 'Over Limit Allowed',
            self::OVER_LIMIT_BLOCKED => 'Over Limit Blocked',
            self::PENDING_REVIEW => 'Pending Review',
        };
    }
}
