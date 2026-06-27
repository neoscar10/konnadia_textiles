<?php

namespace App\Services\StockTransfer;

use App\Models\ProductTransfer;

class TransferDocumentService
{
    /**
     * Get view data for printing/PDF.
     */
    public function viewData(ProductTransfer $transfer): array
    {
        $transfer->load(['shop', 'createdBy', 'items.product', 'items.combination', 'items.unit']);

        return [
            'transfer' => $transfer,
            'shop' => $transfer->shop,
            'creator' => $transfer->createdBy,
            'items' => $transfer->items,
            'pageTitle' => "Product Transfer - {$transfer->transfer_number}",
        ];
    }

    /**
     * Render and return the HTML print view.
     */
    public function download(ProductTransfer $transfer)
    {
        $data = $this->viewData($transfer);
        
        return view('pdf.product-transfer-document', $data);
    }
}
