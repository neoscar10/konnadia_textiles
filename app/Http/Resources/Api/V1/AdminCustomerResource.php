<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'company_name' => $this->company_name,
            'gst_number' => $this->gst_number,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'customer_level_id' => $this->customer_level_id,
            'level' => new CustomerLevelResource($this->whenLoaded('level')),
            'credit_limit' => (float) $this->credit_limit,
            'outstanding_amount' => (float) $this->outstanding_amount,
            'available_credit' => (float) $this->available_credit,
            'overdue_amount' => (float) $this->overdue_amount,
            'allow_credit_beyond_limit' => (bool) $this->allow_credit_beyond_limit,
            'billing_address' => $this->billing_address,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'is_active' => (bool) $this->is_active,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'mobile_number' => $this->user->mobile_number,
                    'is_active' => (bool) $this->user->is_active,
                ] : null;
            }),
            'generated_password' => $this->when(isset($this->generated_password), $this->generated_password),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
