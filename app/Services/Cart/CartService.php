<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Services\Portal\ProductAvailabilityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CartService
{
    protected CartPricingService $pricingService;
    protected ProductAvailabilityService $availabilityService;

    public function __construct(
        CartPricingService $pricingService,
        ProductAvailabilityService $availabilityService
    ) {
        $this->pricingService = $pricingService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Get or create the user's single active cart.
     */
    public function getOrCreateActiveCart(User $user): Cart
    {
        $customer = $user->customer;

        if (!$customer) {
            throw new \RuntimeException('Your user account does not have a linked customer profile.');
        }

        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'status' => 'active',
            ]);
        }

        return $cart;
    }

    /**
     * Get formatted cart data for display.
     */
    public function getCartForCustomer(User $user): array
    {
        $cart = $this->getOrCreateActiveCart($user);
        $cart->load(['items.product.media', 'items.product.primaryMedia', 'items.combination', 'items.unit']);

        // Recalculate to ensure prices are current
        $totals = $this->pricingService->recalculateCart($cart);
        $cart->load(['items.product.media', 'items.product.primaryMedia', 'items.combination', 'items.unit']);

        $items = $cart->items->map(function (CartItem $item) {
            $product = $item->product;
            if (!$product) {
                return null;
            }

            $primaryImage = $product->primaryMedia ? $product->primaryMedia->file_path : null;
            if (!$primaryImage && $product->media->first()) {
                $primaryImage = $product->media->first()->file_path;
            }
            $imageUrl = $primaryImage
                ? (str_starts_with($primaryImage, 'http') ? $primaryImage : Storage::url($primaryImage))
                : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=160';

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_title' => $product->title,
                'product_sku' => $product->sku,
                'product_image_url' => $imageUrl,
                'selected_options' => $item->selected_options,
                'combination_id' => $item->product_combination_id,
                'unit_id' => $item->product_unit_id,
                'unit_name' => $item->unit ? $item->unit->name : 'Piece',
                'unit_short_code' => $item->unit ? $item->unit->short_code : 'Pcs',
                'unit_conversion_quantity' => (float) $item->unit_conversion_quantity,
                'quantity' => $item->quantity,
                'base_unit_price' => (float) $item->base_unit_price,
                'customer_unit_price' => (float) $item->customer_unit_price,
                'line_subtotal' => (float) $item->line_subtotal,
                'gst_percentage' => (float) $item->gst_percentage,
                'gst_amount' => (float) $item->gst_amount,
                'line_total' => (float) $item->line_total,
                'is_active' => $product->is_active,
            ];
        })->filter()->values()->toArray();

        return [
            'cart_id' => $cart->id,
            'items' => $items,
            'totals' => $totals,
        ];
    }

    /**
     * Add item to cart. If same product+combination+unit already exists, increment quantity.
     */
    public function addItem(User $user, array $payload): Cart
    {
        $product = Product::with(['combinations', 'units', 'variationGroups'])->findOrFail($payload['product_id']);
        $combination = isset($payload['combination_id'])
            ? ProductCombination::find($payload['combination_id'])
            : null;
        $unit = isset($payload['unit_id'])
            ? ProductUnit::find($payload['unit_id'])
            : $product->units()->where('level', 1)->first();
        $quantity = (int) ($payload['quantity'] ?? 1);
        $selectedOptions = $payload['selected_options'] ?? null;

        // Validate
        $this->validateItemSelection($product, $combination, $unit, $quantity);

        $cart = $this->getOrCreateActiveCart($user);

        // Check for existing item with same product + combination + unit
        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_combination_id', $combination?->id)
            ->where('product_unit_id', $unit?->id)
            ->first();

        // Calculate pricing
        $pricing = $this->pricingService->calculateCartItem($user, $product, $combination, $unit, $quantity);

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            $pricing = $this->pricingService->calculateCartItem($user, $product, $combination, $unit, $newQuantity);

            $existingItem->update([
                'quantity'                 => $newQuantity,
                'base_unit_price'          => $pricing['base_unit_price'],
                'customer_unit_price'      => $pricing['customer_unit_price'],
                'unit_conversion_quantity' => $pricing['unit_conversion_quantity'],
                'line_subtotal'            => $pricing['line_subtotal'],
                'hsn_code'                 => $pricing['hsn_code'],
                'gst_percentage'           => $pricing['gst_percentage'],
                'gst_amount'               => $pricing['gst_amount'],
                'line_total'               => $pricing['line_total'],
                'selected_options'         => $selectedOptions ?? $existingItem->selected_options,
            ]);
        } else {
            $cart->items()->create([
                'product_id'               => $product->id,
                'product_combination_id'   => $combination?->id,
                'product_unit_id'          => $unit?->id,
                'quantity'                 => $quantity,
                'unit_conversion_quantity' => $pricing['unit_conversion_quantity'],
                'base_unit_price'          => $pricing['base_unit_price'],
                'customer_unit_price'      => $pricing['customer_unit_price'],
                'line_subtotal'            => $pricing['line_subtotal'],
                'hsn_code'                 => $pricing['hsn_code'],
                'gst_percentage'           => $pricing['gst_percentage'],
                'gst_amount'               => $pricing['gst_amount'],
                'line_total'               => $pricing['line_total'],
                'selected_options'         => $selectedOptions,
            ]);
        }

        return $cart->fresh();
    }

    /**
     * Update an existing cart item's quantity.
     */
    public function updateItem(User $user, CartItem $item, array $payload): Cart
    {
        $cart = $this->getOrCreateActiveCart($user);

        if ($item->cart_id !== $cart->id) {
            throw ValidationException::withMessages(['item' => 'This item does not belong to your cart.']);
        }

        $quantity = (int) ($payload['quantity'] ?? $item->quantity);
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $product = $item->product;
        $combination = $item->combination;
        $unit = $item->unit ?? $product->units()->where('level', 1)->first();

        $pricing = $this->pricingService->calculateCartItem($user, $product, $combination, $unit, $quantity);

        $item->update([
            'quantity'                 => $quantity,
            'base_unit_price'          => $pricing['base_unit_price'],
            'customer_unit_price'      => $pricing['customer_unit_price'],
            'unit_conversion_quantity' => $pricing['unit_conversion_quantity'],
            'line_subtotal'            => $pricing['line_subtotal'],
            'hsn_code'                 => $pricing['hsn_code'],
            'gst_percentage'           => $pricing['gst_percentage'],
            'gst_amount'               => $pricing['gst_amount'],
            'line_total'               => $pricing['line_total'],
        ]);

        return $cart->fresh();
    }

    /**
     * Remove a cart item.
     */
    public function removeItem(User $user, CartItem $item): Cart
    {
        $cart = $this->getOrCreateActiveCart($user);

        if ($item->cart_id !== $cart->id) {
            throw ValidationException::withMessages(['item' => 'This item does not belong to your cart.']);
        }

        $item->delete();

        return $cart->fresh();
    }

    /**
     * Clear all items from active cart.
     */
    public function clearCart(User $user): void
    {
        $cart = $this->getOrCreateActiveCart($user);
        $cart->items()->delete();
    }

    /**
     * Validate product/combination/unit/quantity selection.
     */
    public function validateItemSelection(
        Product $product,
        ?ProductCombination $combination,
        ?ProductUnit $unit,
        int $quantity
    ): void {
        $errors = [];

        if (!$product->is_active) {
            $errors['product_id'] = 'This product is not available.';
        }

        if ($quantity < 1) {
            $errors['quantity'] = 'Quantity must be at least 1.';
        }

        // GST must be explicitly configured. null = not configured, 0 = zero-rated (allowed).
        if ($product->gst_percentage === null) {
            $errors['product_id'] = 'This product is missing GST configuration and cannot be added to cart. Please contact support.';
        }

        // If product has variation groups, a valid combination is required
        $hasVariations = $product->variationGroups()->exists();
        if ($hasVariations && !$combination) {
            $errors['combination_id'] = 'Please select all required product options.';
        }

        if ($combination) {
            if ($combination->product_id !== $product->id) {
                $errors['combination_id'] = 'Selected product combination is not available.';
            } elseif (!$combination->is_active) {
                $errors['combination_id'] = 'Selected product combination is not available.';
            }
        }

        if ($unit && $unit->product_id !== $product->id) {
            $errors['unit_id'] = 'Selected unit does not belong to this product.';
        }

        // Check stock
        if (!$this->availabilityService->isPurchasable($product, $combination)) {
            $errors['stock'] = 'This product is currently out of stock.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Get cart item count for the active cart.
     */
    public function getCartItemCount(User $user): int
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $cart ? $cart->items()->count() : 0;
    }
}
