<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * List user device tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->deviceTokens()->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Register or update an FCM device token for mobile / web push notifications.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string|max:500',
            'platform'     => 'nullable|string|in:android,ios,web',
            'device_name'  => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $token = UserDeviceToken::updateOrCreate(
            [
                'user_id'      => $user->id,
                'device_token' => $validated['device_token'],
            ],
            [
                'platform'     => $validated['platform'] ?? 'android',
                'device_name'  => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully for push notifications.',
            'data'    => $token,
        ], 201);
    }

    /**
     * Unregister a device token (e.g. on logout or disable push notifications).
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string|max:500',
        ]);

        $deleted = $request->user()->deviceTokens()
            ->where('device_token', $validated['device_token'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Device token unregistered successfully.' : 'Token not found.',
        ]);
    }
}
