<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'whatsapp_number' => $this->whatsapp_number,
            'email' => $this->email,
            'portal_access' => (bool) $this->portal_access,
            'address' => $this->address,
            'gstin' => $this->gstin,
            'notes' => $this->notes,
            'inventory_batches_count' => $this->whenCounted('inventoryBatches'),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
