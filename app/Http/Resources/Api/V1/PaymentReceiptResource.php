<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $filePath = $this->file_path;
        $fileUrl = $filePath
            ? (str_starts_with($filePath, 'http') ? $filePath : url(Storage::url($filePath)))
            : null;

        return [
            'id' => $this->id,
            'file_url' => $fileUrl,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => (int) $this->size,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'verified_at' => $this->verified_at ? $this->verified_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
