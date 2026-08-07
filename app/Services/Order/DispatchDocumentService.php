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
            // Check if parameter is an order number or order ID
            $order = Order::where('order_number', $dispatchNumber)
                ->orWhere('id', $dispatchNumber)
                ->first();

            if ($order) {
                $items = OrderItem::where('order_id', $order->id)
                    ->whereNotNull('dispatch_number')
                    ->with(['product', 'combination', 'unit', 'dispatchedBy'])
                    ->get();
            }
        }

        if ($items->isEmpty()) {
            abort(404, 'Dispatch document not found or no items dispatched for this order.');
        }

        // Get the order of the first item
        $order = $items->first()->order;
        $order->load(['customer.level']);

        $dispNum = $items->first()->dispatch_number ?: $dispatchNumber;

        return [
            'dispatchNumber' => $dispNum,
            'order' => $order,
            'items' => $items,
            'customer' => $order->customer,
            'dispatchedAt' => $items->first()->dispatched_at,
            'dispatchedBy' => $items->first()->dispatchedBy,
            'pageTitle' => "Dispatch Document - {$dispNum}",
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
