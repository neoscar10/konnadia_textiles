<?php

namespace App\Services\Order;

use App\Models\OrderItem;
use App\Models\Order;

class DispatchDocumentService
{
    /**
     * Get view data for printing/PDF.
     */
    public function viewData(string $dispatchNumber): array
    {
        // Load items belonging to this dispatch run
        $items = OrderItem::where('dispatch_number', $dispatchNumber)
            ->with(['product', 'combination', 'unit', 'dispatchedBy'])
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dispatch document not found.');
        }

        // Get the order of the first item
        $order = $items->first()->order;
        $order->load(['customer.level']);

        return [
            'dispatchNumber' => $dispatchNumber,
            'order' => $order,
            'items' => $items,
            'customer' => $order->customer,
            'dispatchedAt' => $items->first()->dispatched_at,
            'dispatchedBy' => $items->first()->dispatchedBy,
            'pageTitle' => "Dispatch Document - {$dispatchNumber}",
        ];
    }

    /**
     * Render and return the HTML print view.
     */
    public function download(string $dispatchNumber)
    {
        $data = $this->viewData($dispatchNumber);
        
        return view('pdf.order-dispatch-document', $data);
    }
}
