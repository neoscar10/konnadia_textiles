<?php

namespace App\Services\Notification;

use App\Models\AdminNotificationContact;
use App\Models\Order;
use App\Models\ProductTransfer;
use Illuminate\Support\Facades\Log;

class AdminWhatsAppNotificationService
{
    protected WhatsAppNotificationService $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Send WhatsApp alert to admins when a new order is placed.
     */
    public function notifyNewOrder(Order $order): array
    {
        $templateName = config('services.waty.admin_new_order_template', env('WATY_WHATSAPP_ADMIN_NEW_ORDER_TEMPLATE', 'admin_new_order_alert'));
        $contacts = AdminNotificationContact::subscribedToNewOrders()->get();

        $recipientPhones = $contacts->pluck('phone_number')->toArray();
        if (empty($recipientPhones)) {
            $fallbackPhone = config('services.waty.admin_phone_number');
            if ($fallbackPhone) {
                $recipientPhones = [$fallbackPhone];
            }
        }

        if (empty($recipientPhones)) {
            Log::info("[Admin WhatsApp Alert] No admin phone numbers configured for new order alert.");
            return ['sent' => false, 'reason' => 'No active admin notification contacts configured.'];
        }

        $customerName = $order->customer ? ($order->customer->company_name ?? $order->customer->user?->name ?? 'Customer') : ($order->user?->name ?? 'Customer');
        $itemsCount = $order->orderItems ? $order->orderItems->count() . ' items' : 'Multiple items';
        $totalAmount = '₹' . number_format($order->total_amount, 2);
        $orderDate = $order->created_at ? $order->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A');

        $bodyParams = [
            $order->order_number,
            $customerName,
            $totalAmount,
            $itemsCount,
            $orderDate,
        ];

        $results = [];
        foreach ($recipientPhones as $phone) {
            $res = $this->whatsAppService->sendTemplateMessage($phone, $templateName, $bodyParams);
            $results[$phone] = $res;
        }

        return ['success' => true, 'recipients_count' => count($recipientPhones), 'results' => $results];
    }

    /**
     * Send WhatsApp alert to admins when a stock / retail goods transfer occurs.
     */
    public function notifyGoodsTransfer(ProductTransfer $transfer): array
    {
        $templateName = config('services.waty.admin_goods_transfer_template', env('WATY_WHATSAPP_ADMIN_GOODS_TRANSFER_TEMPLATE', 'admin_goods_transfer_alert'));
        $contacts = AdminNotificationContact::subscribedToGoodsTransfers()->get();

        $recipientPhones = $contacts->pluck('phone_number')->toArray();
        if (empty($recipientPhones)) {
            $fallbackPhone = config('services.waty.admin_phone_number');
            if ($fallbackPhone) {
                $recipientPhones = [$fallbackPhone];
            }
        }

        if (empty($recipientPhones)) {
            Log::info("[Admin WhatsApp Alert] No admin phone numbers configured for goods transfer alert.");
            return ['sent' => false, 'reason' => 'No active admin notification contacts configured.'];
        }

        $transfer->loadMissing(['shop', 'createdBy', 'items']);

        $sourceShop = 'Main Factory Warehouse';
        $destinationShop = $transfer->shop ? $transfer->shop->name : 'Retail Shop';
        $totalQty = $transfer->items->sum('quantity');
        $itemsSummary = $transfer->items->count() . ' product(s) (' . number_format($totalQty) . ' total qty)';
        $creatorName = $transfer->createdBy ? $transfer->createdBy->name : 'Admin';
        $transferDate = $transfer->transfer_date ? \Carbon\Carbon::parse($transfer->transfer_date)->format('d M Y') : now()->format('d M Y');

        $bodyParams = [
            $transfer->transfer_number,
            $sourceShop,
            $destinationShop,
            $itemsSummary,
            "{$creatorName} on {$transferDate}",
        ];

        $results = [];
        foreach ($recipientPhones as $phone) {
            $res = $this->whatsAppService->sendTemplateMessage($phone, $templateName, $bodyParams);
            $results[$phone] = $res;
        }

        return ['success' => true, 'recipients_count' => count($recipientPhones), 'results' => $results];
    }

    /**
     * Send WhatsApp alert to admins when order items or status are dispatched.
     */
    public function notifyOrderDispatch(Order $order, array $dispatchDetails = []): array
    {
        $templateName = config('services.waty.admin_order_dispatch_template', env('WATY_WHATSAPP_ADMIN_ORDER_DISPATCH_TEMPLATE', 'admin_order_dispatch_alert'));
        $contacts = AdminNotificationContact::subscribedToOrderDispatches()->get();

        $recipientPhones = $contacts->pluck('phone_number')->toArray();
        if (empty($recipientPhones)) {
            $fallbackPhone = config('services.waty.admin_phone_number');
            if ($fallbackPhone) {
                $recipientPhones = [$fallbackPhone];
            }
        }

        if (empty($recipientPhones)) {
            Log::info("[Admin WhatsApp Alert] No admin phone numbers configured for order dispatch alert.");
            return ['sent' => false, 'reason' => 'No active admin notification contacts configured.'];
        }

        $customerName = $order->customer ? ($order->customer->company_name ?? $order->customer->user?->name ?? 'Customer') : ($order->user?->name ?? 'Customer');
        $dispatchStatusLabel = match ($order->status) {
            'partially_dispatched' => 'Partially Dispatched',
            'dispatched' => 'Fully Dispatched',
            default => str_replace('_', ' ', ucfirst($order->status)),
        };

        $dispatchedCount = $order->orderItems ? $order->orderItems->where('status', 'dispatched')->count() : 0;
        $totalItemsCount = $order->orderItems ? $order->orderItems->count() : 0;
        $dispatchSummary = "{$dispatchedCount} of {$totalItemsCount} items dispatched";

        $trackingInfo = $dispatchDetails['tracking_number'] ?? $dispatchDetails['courier_name'] ?? 'N/A';

        $bodyParams = [
            $order->order_number,
            $customerName,
            $dispatchStatusLabel,
            $dispatchSummary,
            $trackingInfo,
        ];

        $results = [];
        foreach ($recipientPhones as $phone) {
            $res = $this->whatsAppService->sendTemplateMessage($phone, $templateName, $bodyParams);
            $results[$phone] = $res;
        }

        return ['success' => true, 'recipients_count' => count($recipientPhones), 'results' => $results];
    }
}
