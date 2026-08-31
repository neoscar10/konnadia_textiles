<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockReplenishedNotification extends Notification
{
    use Queueable;

    public Product $product;
    public ?ProductCombination $combination;
    public ?ProductUnit $unit;

    public function __construct(Product $product, ?ProductCombination $combination = null, ?ProductUnit $unit = null)
    {
        $this->product = $product;
        $this->combination = $combination;
        $this->unit = $unit;
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
        $itemTitle = $this->product->title;
        if ($this->combination) {
            $itemTitle .= ' (' . implode(', ', array_values($this->combination->attribute_values ?? [])) . ')';
        }
        if ($this->unit) {
            $itemTitle .= ' - ' . $this->unit->name;
        }

        return [
            'type' => 'stock_replenished',
            'title' => 'Product Back in Stock!',
            'message' => "\"{$itemTitle}\" is now back in stock and available for order.",
            'product_id' => $this->product->id,
            'product_combination_id' => $this->combination?->id,
            'product_unit_id' => $this->unit?->id,
            'product_title' => $this->product->title,
            'action_url' => url("/products/{$this->product->slug}"),
        ];
    }
}
