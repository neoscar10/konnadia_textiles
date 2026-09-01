<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductStockReminder;
use App\Services\Inventory\StockReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReminderController extends Controller
{
    protected StockReminderService $reminderService;

    public function __construct(StockReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * List active stock reminders for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $reminders = ProductStockReminder::with(['product.primaryMedia', 'combination', 'unit'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $reminders->map(fn($r) => [
                'id' => $r->id,
                'product_id' => $r->product_id,
                'product_title' => $r->product ? $r->product->title : 'N/A',
                'product_combination_id' => $r->product_combination_id,
                'combination_attributes' => $r->combination ? $r->combination->attribute_values : null,
                'product_unit_id' => $r->product_unit_id,
                'unit_name' => $r->unit ? $r->unit->name : null,
                'unit_level' => $r->unit ? $r->unit->level : 1,
                'quantity' => (float) $r->quantity,
                'phone_number' => $r->phone_number,
                'email' => $r->email,
                'status' => $r->status,
                'created_at' => $r->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Subscribe to a product stock reminder.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'             => 'required|integer|exists:products,id',
            'product_combination_id' => 'nullable|integer|exists:product_combinations,id',
            'product_unit_id'        => 'nullable|integer|exists:product_units,id',
            'quantity'               => 'nullable|numeric|min:0.01',
            'phone_number'           => 'nullable|string|max:30',
            'email'                  => 'nullable|email|max:255',
        ]);

        $user = $request->user('api') ?? $request->user();
        $reminder = $this->reminderService->createReminder($validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Stock reminder set successfully. We will notify you when this item becomes available.',
            'data'    => [
                'id' => $reminder->id,
                'product_id' => $reminder->product_id,
                'product_combination_id' => $reminder->product_combination_id,
                'product_unit_id' => $reminder->product_unit_id,
                'quantity' => (float) $reminder->quantity,
                'status' => $reminder->status,
            ],
        ], 201);
    }

    /**
     * Cancel a pending stock reminder.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user('api') ?? $request->user();
        $success = $this->reminderService->cancelReminder($id, $user);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Stock reminder not found or already cancelled.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock reminder cancelled successfully.',
        ]);
    }
}
