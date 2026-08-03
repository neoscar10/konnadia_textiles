<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminUnitController extends Controller
{
    /**
     * Get system unit templates & level configurations.
     */
    public function index(): JsonResponse
    {
        $units = [
            'default_level1' => [
                'name' => 'Piece',
                'short_code' => 'pcs',
                'conversion_to_base' => 1.0,
            ],
            'common_level2_templates' => [
                ['name' => 'Set (4 Pcs)', 'short_code' => 'set', 'conversion_to_base' => 4.0],
                ['name' => 'Box (10 Pcs)', 'short_code' => 'box', 'conversion_to_base' => 10.0],
                ['name' => 'Carton (50 Pcs)', 'short_code' => 'ctn', 'conversion_to_base' => 50.0],
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }
}
