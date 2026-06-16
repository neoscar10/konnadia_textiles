<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\SubmitCheckoutRequest;
use App\Http\Resources\Api\V1\CheckoutSummaryResource;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * Get the checkout summary including cart totals and credit eligibility.
     */
    public function summary(Request $request): JsonResponse
    {
        $summaryData = $this->checkoutService->getCheckoutSummary($request->user());
        return (new CheckoutSummaryResource($summaryData))
            ->additional([
                'success' => true,
                'message' => 'Checkout summary retrieved successfully.',
            ])
            ->response();
    }

    /**
     * Submit an order.
     */
    public function submit(SubmitCheckoutRequest $request): JsonResponse
    {
        // For multipart/form-data, uploaded file will be in $request->file('receipt_file')
        // which matches payload parameter expected by checkoutService.
        $payload = $request->validated();
        if ($request->hasFile('receipt_file')) {
            $payload['receipt_file'] = $request->file('receipt_file');
        }

        $order = $this->checkoutService->submitOrder($request->user(), $payload);

        return (new OrderDetailResource($order))
            ->additional([
                'success' => true,
                'message' => 'Order submitted successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
