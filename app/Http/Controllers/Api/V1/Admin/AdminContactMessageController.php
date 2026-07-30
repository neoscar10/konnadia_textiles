<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminContactMessageController extends Controller
{
    /**
     * Get contact message statistics.
     */
    public function stats(): JsonResponse
    {
        $total = ContactMessage::count();
        $unread = ContactMessage::where('is_read', false)->count();
        $read = ContactMessage::where('is_read', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_messages' => $total,
                'unread_messages' => $unread,
                'read_messages' => $read,
            ]
        ]);
    }

    /**
     * List contact messages with search and read/unread status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'read') {
                $query->where('is_read', true);
            } elseif ($status === 'unread') {
                $query->where('is_read', false);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'name' => $msg->name,
                    'email' => $msg->email,
                    'phone' => $msg->phone ?? null,
                    'subject' => $msg->subject,
                    'message' => $msg->message,
                    'is_read' => (bool) $msg->is_read,
                    'created_at' => $msg->created_at ? $msg->created_at->format('d-M-Y H:i') : null,
                ];
            }),
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
     * Get single contact message detail and mark as read.
     */
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);

        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone ?? null,
                'subject' => $message->subject,
                'message' => $message->message,
                'is_read' => (bool) $message->is_read,
                'created_at' => $message->created_at ? $message->created_at->format('d-M-Y H:i') : null,
            ]
        ]);
    }

    /**
     * Mark message as unread.
     */
    public function markUnread(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['is_read' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as unread successfully.',
            'data' => [
                'id' => $message->id,
                'is_read' => false,
            ]
        ]);
    }

    /**
     * Delete contact message.
     */
    public function destroy(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }
}
