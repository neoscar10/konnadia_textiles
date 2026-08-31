<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public Order $order;
    public string $fromStatus;
    public string $toStatus;
    public string $statusLabel;
    public ?string $note;

    public function __construct(Order $order, string $fromStatus, string $toStatus, string $statusLabel, ?string $note = null)
    {
        $this->order = $order;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->statusLabel = $statusLabel;
        $this->note = $note;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_updated',
            'title' => "Order #{$this->order->order_number} Status Updated",
            'message' => "Your order #{$this->order->order_number} status is now \"{$this->statusLabel}\".",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'status_label' => $this->statusLabel,
            'note' => $this->note,
            'action_url' => url("/portal/orders/{$this->order->order_number}"),
        ];
    }
}
