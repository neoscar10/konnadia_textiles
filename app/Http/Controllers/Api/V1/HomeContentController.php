<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Home\HomeContentRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeContentController extends Controller
{
    protected HomeContentRenderService $renderService;

    public function __construct(HomeContentRenderService $renderService)
    {
        $this->renderService = $renderService;
    }

    /**
     * Get dynamic customer home content sections.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Extra safeguard (optional but good practice)
        if (!$user || !$user->hasRole('customer') || !$user->customer || !$user->customer->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only active customer accounts are allowed.',
                'errors' => new \stdClass()
            ], 403);
        }

        $sections = $this->renderService->getHomeContentForCustomer($user);

        return response()->json([
            'success' => true,
            'message' => 'Home content retrieved successfully.',
            'data' => $sections,
        ]);
    }
}
