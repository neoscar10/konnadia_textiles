<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shop = $this->shop ?? $this->retailShop;
        $creator = $this->createdBy ?? $this->creator;

        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'reference_number' => $this->transfer_number ?? $this->reference_number,
            'transfer_date' => $this->transfer_date ? $this->transfer_date->format('d-M-Y') : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'total_base_quantity' => (float) $this->total_quantity_base_units,
            'items_count' => $this->items_count ?? ($this->relationLoaded('items') ? $this->items->count() : $this->items()->count()),
            'retail_shop' => $shop ? [
                'id' => $shop->id,
                'shop_code' => $shop->shop_code,
                'code' => $shop->shop_code,
                'name' => $shop->name,
                'city' => $shop->city,
            ] : null,
            'creator' => $creator ? [
                'id' => $creator->id,
                'name' => $creator->name,
                'email' => $creator->email,
            ] : null,
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
        ];
    }
}
