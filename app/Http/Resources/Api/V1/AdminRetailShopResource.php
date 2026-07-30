<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRetailShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_code' => $this->shop_code,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'is_active' => (bool) $this->is_active,
            'transfers_count' => $this->transfers()->count(),
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
