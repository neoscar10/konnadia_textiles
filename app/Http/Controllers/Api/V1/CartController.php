<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the active cart.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart($request->user());
        return (new CartResource($cart))
            ->additional([
                'success' => true,
                'message' => 'Cart retrieved successfully.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem($request->user(), $request->validated());
        return (new CartResource($cart))
            ->additional([
                'success' => true,
                'message' => 'Item added to cart successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an item in the cart.
     */
    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->updateItem($request->user(), $cartItem, $request->validated());
        return (new CartResource($cart))
            ->additional([
                'success' => true,
                'message' => 'Cart item updated successfully.',
            ])
            ->response();
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->removeItem($request->user(), $cartItem);
        return (new CartResource($cart))
            ->additional([
                'success' => true,
                'message' => 'Cart item removed successfully.',
            ])
            ->response();
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());
        $cart = $this->cartService->getOrCreateActiveCart($request->user());
        return (new CartResource($cart))
            ->additional([
                'success' => true,
                'message' => 'Cart cleared successfully.',
            ])
            ->response();
    }
}
