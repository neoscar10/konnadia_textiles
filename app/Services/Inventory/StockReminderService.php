<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Models\ProductStockReminder;
use App\Models\User;
use App\Notifications\StockReplenishedNotification;
use App\Services\Notification\FcmPushNotificationService;
use App\Services\Notification\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StockReminderService
{
    protected FcmPushNotificationService $fcmService;
    protected WhatsAppNotificationService $whatsAppService;

    public function __construct(
        FcmPushNotificationService $fcmService,
        WhatsAppNotificationService $whatsAppService
    ) {
        $this->fcmService = $fcmService;
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Subscribe a user or guest to a product stock reminder.
     */
    public function createReminder(array $payload, ?User $user = null): ProductStockReminder
    {
        $product = Product::findOrFail($payload['product_id']);
        $combinationId = isset($payload['product_combination_id']) ? (int)$payload['product_combination_id'] : null;
        $unitId = isset($payload['product_unit_id']) ? (int)$payload['product_unit_id'] : null;

        $phone = $payload['phone_number'] ?? $user?->mobile_number ?? $user?->customer?->phone;
        $email = $payload['email'] ?? $user?->email;

        // Ensure user or phone/email provided
        if (!$user && empty($phone) && empty($email)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Please provide a valid phone number or email address for stock notification.',
            ]);
        }

        // Check for existing pending reminder
        $query = ProductStockReminder::pending()
            ->where('product_id', $product->id)
            ->where('product_combination_id', $combinationId)
            ->where('product_unit_id', $unitId);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where(function ($q) use ($phone, $email) {
                if ($phone) $q->where('phone_number', $phone);
                if ($email) $q->orWhere('email', $email);
            });
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        return ProductStockReminder::create([
            'user_id'                => $user?->id,
            'product_id'             => $product->id,
            'product_combination_id' => $combinationId,
            'product_unit_id'        => $unitId,
            'phone_number'           => $phone,
            'email'                  => $email,
            'status'                 => 'pending',
        ]);
    }

    /**
     * Cancel a pending reminder.
     */
    public function cancelReminder(int $reminderId, ?User $user = null): bool
    {
        $query = ProductStockReminder::where('id', $reminderId);
        if ($user) {
            $query->where('user_id', $user->id);
        }

        $reminder = $query->first();
        if (!$reminder) {
            return false;
        }

        return $reminder->update(['status' => 'cancelled']);
    }

    /**
     * Check stock replenishment and notify subscribers via Database, FCM Push, and WhatsApp.
     */
    public function notifySubscribersIfReplenished(Product $product, ?ProductCombination $combination = null): int
    {
        $query = ProductStockReminder::pending()->where('product_id', $product->id);

        if ($combination) {
            $query->where(function ($q) use ($combination) {
                $q->where('product_combination_id', $combination->id)
                  ->orWhereNull('product_combination_id');
            });
        }

        $pendingReminders = $query->get();
        if ($pendingReminders->isEmpty()) {
            return 0;
        }

        $notifiedCount = 0;
        $templateName = config('services.whatsapp.stock_template', env('WHATSAPP_STOCK_TEMPLATE', 'product_back_in_stock'));

        foreach ($pendingReminders as $reminder) {
            $unit = $reminder->unit;

            // 1. Database / In-App Notification (for logged-in user)
            if ($reminder->user) {
                try {
                    $reminder->user->notify(new StockReplenishedNotification($product, $combination, $unit));
                } catch (\Throwable $e) {
                    Log::error("[StockReminderService] Error sending DB notification: " . $e->getMessage());
                }

                // 2. FCM Push Notification (Mobile App)
                $tokens = $reminder->user->deviceTokens()->pluck('device_token')->toArray();
                if (!empty($tokens)) {
                    $title = "Back in Stock: {$product->title}";
                    $body = "Good news! {$product->title} is back in stock. Tap to view and place your order.";
                    $this->fcmService->sendPush($tokens, $title, $body, [
                        'type' => 'stock_replenished',
                        'product_id' => (string) $product->id,
                        'combination_id' => (string) ($combination?->id ?? ''),
                        'unit_id' => (string) ($unit?->id ?? ''),
                    ]);
                }
            }

            // 3. WhatsApp Notification
            $phone = $reminder->phone_number ?? $reminder->user?->mobile_number ?? $reminder->user?->customer?->phone;
            if ($phone) {
                $productName = $product->title;
                if ($combination) {
                    $productName .= ' (' . implode(', ', array_values($combination->attribute_values ?? [])) . ')';
                }
                if ($unit) {
                    $productName .= ' - ' . $unit->name;
                }

                $this->whatsAppService->sendTemplateMessage(
                    $phone,
                    $templateName,
                    [$productName, url("/products/{$product->slug}")]
                );
            }

            $reminder->update([
                'status' => 'notified',
                'notified_at' => now(),
            ]);

            $notifiedCount++;
        }

        Log::info("[StockReminderService] Notified {$notifiedCount} subscriber(s) for product \"{$product->title}\".");
        return $notifiedCount;
    }
}
