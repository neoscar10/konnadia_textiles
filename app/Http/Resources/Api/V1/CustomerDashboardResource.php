<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer' => $this->resource['customer'] ?? null,
            'credit' => $this->resource['credit'] ?? null,
            'cart' => $this->resource['cart'] ?? null,
            'orders' => $this->resource['orders'] ?? null,
            'recent_orders' => $this->resource['recent_orders'] ?? null,
            'alerts' => $this->resource['alerts'] ?? null,
            'quick_actions' => $this->resource['quick_actions'] ?? null,
            'recommended_products' => $this->resource['recommended_products'] ?? null,
        ];
    }
}
