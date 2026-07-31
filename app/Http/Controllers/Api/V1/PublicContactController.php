<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicContactController extends Controller
{
    /**
     * Submit a contact message inquiry from the mobile app / website contact form.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $message = ContactMessage::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'subject' => $validated['subject'] ? trim($validated['subject']) : 'General Inquiry',
            'message' => trim($validated['message']),
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully. Our team will contact you shortly.',
            'data' => [
                'id' => $message->id,
                'created_at' => $message->created_at ? $message->created_at->format('d-M-Y H:i') : null,
            ]
        ], 201);
    }
}
