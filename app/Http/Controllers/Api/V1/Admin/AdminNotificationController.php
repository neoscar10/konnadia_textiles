<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    /**
     * List authenticated admin notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $user->unreadNotifications->count();

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $user->notifications()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection(),
            'unread_count' => $unreadCount,
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|string',
        ]);

        $notification = $request->user()->notifications()->where('id', $request->input('notification_id'))->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
