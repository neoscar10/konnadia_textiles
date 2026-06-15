<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $level = $this->level;

        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'company_name' => $this->company_name,
            'gst_number' => $this->gst_number,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'customer_level' => $level ? [
                'id' => $level->id,
                'name' => $level->name,
                'discount_percentage' => (float)$level->discount_percentage,
            ] : null,
            'credit' => [
                'credit_limit' => (float)$this->credit_limit,
                'outstanding_amount' => (float)$this->outstanding_amount,
                'available_credit' => (float)$this->available_credit,
                'overdue_amount' => (float)$this->overdue_amount,
                'allow_credit_beyond_limit' => (bool)$this->allow_credit_beyond_limit,
            ],
            'is_active' => (bool)$this->is_active,
        ];
    }
}
