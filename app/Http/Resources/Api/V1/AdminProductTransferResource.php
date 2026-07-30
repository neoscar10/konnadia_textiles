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
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'transfer_date' => $this->transfer_date ? $this->transfer_date->format('d-M-Y') : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'total_base_quantity' => (float) $this->total_quantity_base_units,
            'items_count' => $this->items_count ?? $this->items()->count(),
            'retail_shop' => $this->retailShop ? [
                'id' => $this->retailShop->id,
                'code' => $this->retailShop->code,
                'name' => $this->retailShop->name,
                'city' => $this->retailShop->city,
            ] : null,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null,
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
        ];
    }
}
