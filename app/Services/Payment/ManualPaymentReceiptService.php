<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\OrderPaymentReceipt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ManualPaymentReceiptService
{
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
    protected int $maxSizeBytes = 5 * 1024 * 1024; // 5MB

    /**
     * Validate and store a payment receipt file.
     */
    public function storeReceipt(Order $order, UploadedFile $file): OrderPaymentReceipt
    {
        $this->validateReceiptFile($file);

        $path = $file->store('payment-receipts', 'public');

        return OrderPaymentReceipt::create([
            'order_id' => $order->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending_verification',
        ]);
    }

    /**
     * Validate a receipt file.
     */
    public function validateReceiptFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions)) {
            throw ValidationException::withMessages([
                'receipt_file' => 'Invalid file type. Allowed: ' . implode(', ', $this->allowedExtensions) . '.',
            ]);
        }

        if ($file->getSize() > $this->maxSizeBytes) {
            throw ValidationException::withMessages([
                'receipt_file' => 'File is too large. Maximum size: 5MB.',
            ]);
        }
    }
}
