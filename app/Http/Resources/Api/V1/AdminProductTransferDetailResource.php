<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductTransferDetailResource extends JsonResource
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
            'retail_shop' => $this->retailShop ? [
                'id' => $this->retailShop->id,
                'shop_code' => $this->retailShop->shop_code,
                'name' => $this->retailShop->name,
                'address' => $this->retailShop->address,
                'city' => $this->retailShop->city,
                'state' => $this->retailShop->state,
                'contact_person' => $this->retailShop->contact_person,
                'contact_phone' => $this->retailShop->contact_phone,
            ] : null,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null,
            'items' => $this->items->map(function ($item) {
                $combinationName = 'None';
                if ($item->combination) {
                    if (is_array($item->combination->combination_values)) {
                        $opts = [];
                        foreach ($item->combination->combination_values as $grp => $val) {
                            $opts[] = "{$grp}: {$val}";
                        }
                        $combinationName = implode(', ', $opts);
                    }
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_title' => $item->product ? $item->product->title : 'Deleted Product',
                    'product_sku' => $item->combination ? $item->combination->sku : ($item->product ? $item->product->sku : null),
                    'product_combination_id' => $item->product_combination_id,
                    'combination_name' => $combinationName,
                    'product_unit_id' => $item->product_unit_id,
                    'unit_name' => $item->unit ? $item->unit->name : null,
                    'unit_short_code' => $item->unit ? $item->unit->short_code : null,
                    'quantity' => (int) $item->quantity,
                    'unit_conversion_quantity' => (float) $item->unit_conversion_quantity,
                    'base_quantity' => (float) $item->base_quantity,
                    'note' => $item->note,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
