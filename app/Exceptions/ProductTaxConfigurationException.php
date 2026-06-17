<?php

namespace App\Exceptions;

use RuntimeException;

class ProductTaxConfigurationException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: 'This product is missing GST configuration and cannot be purchased. Please contact support.'
        );
    }
}
