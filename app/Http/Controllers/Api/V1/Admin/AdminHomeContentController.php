<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeContentSection;
use App\Models\Category;
use App\Models\Product;
use App\Services\Home\AdminHomeContentService;
use App\Services\Home\HomeContentMediaService;
use App\Services\Home\HomeContentRenderService;
use App\Http\Requests\Api\V1\Admin\StoreHomeContentSectionRequest;
use App\Http\Requests\Api\V1\Admin\ReorderHomeContentSectionsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminHomeContentController extends Controller
{
    /**
     * Get home content dashboard stats.
     */
    public function stats(): JsonResponse
    {
        $total = HomeContentSection::count();
        $active = HomeContentSection::where('is_active', true)->count();
        $inactive = HomeContentSection::where('is_active', false)->count();
        $scheduled = HomeContentSection::whereNotNull('starts_at')->orWhereNotNull('ends_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sections' => $total,
                'active_sections' => $active,
                'inactive_sections' => $inactive,
                'scheduled_sections' => $scheduled,
            ]
        ]);
    }

    /**
     * List home content sections.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HomeContentSection::ordered()->withCount('items');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(function ($section) {
                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'position' => $section->position,
                    'is_active' => (bool) $section->is_active,
                    'items_count' => $section->items_count,
                    'starts_at' => $section->starts_at ? $section->starts_at->toDateTimeString() : null,
                    'ends_at' => $section->ends_at ? $section->ends_at->toDateTimeString() : null,
                    'created_at' => $section->created_at ? $section->created_at->format('d-M-Y H:i') : null,
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
     * Fetch options metadata for creating/editing sections.
     */
    public function options(Request $request): JsonResponse
    {
        $categories = Category::orderBy('name')->get(['id', 'title', 'slug', 'parent_id']);

        $productQuery = Product::where('is_active', true)->with('primaryMedia');
        if ($search = $request->query('product_search')) {
            $productQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        if ($catId = $request->query('category_id')) {
            $productQuery->whereHas('categories', function ($q) use ($catId) {
                $q->where('categories.id', $catId);
            });
        }
        $products = $productQuery->take(50)->get()->map(function ($prod) {
            return [
                'id' => $prod->id,
                'title' => $prod->title,
                'sku' => $prod->sku,
                'base_price' => (float) $prod->base_price,
                'stock_quantity' => (int) $prod->stock_quantity,
                'primary_image_url' => $prod->primaryMedia ? asset('storage/' . $prod->primaryMedia->file_path) : null,
            ];
        });

        $sectionTypes = [
            ['key' => 'banner', 'label' => 'Single Hero Banner'],
            ['key' => 'banner_slider', 'label' => 'Hero Banner Slider'],
            ['key' => 'image_slider', 'label' => 'Image Carousel'],
            ['key' => 'category_slider', 'label' => 'Category Grid / Slider'],
            ['key' => 'product_slider', 'label' => 'Product Grid / Slider'],
            ['key' => 'image_text_card', 'label' => 'Rich Image & Text Card'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'section_types' => $sectionTypes,
                'categories' => $categories,
                'products' => $products,
            ]
        ]);
    }

    /**
     * Get single section details.
     */
    public function show(int $id, HomeContentRenderService $renderService): JsonResponse
    {
        $section = HomeContentSection::with('items')->findOrFail($id);
        $rendered = $renderService->formatSection($section);

        return response()->json([
            'success' => true,
            'data' => [
                'section' => $section,
                'rendered_content' => $rendered,
            ]
        ]);
    }

    /**
     * Create new home content section.
     */
    public function store(StoreHomeContentSectionRequest $request, AdminHomeContentService $adminService, HomeContentMediaService $mediaService): JsonResponse
    {
        $validated = $request->validated();

        $sectionData = [
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_style' => $validated['display_style'] ?? null,
            'items_per_view' => $validated['items_per_view'] ?? 4,
            'display_limit' => $validated['display_limit'] ?? 10,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'items' => $validated['items'] ?? [],
        ];

        $section = $adminService->createSection($sectionData);

        return response()->json([
            'success' => true,
            'message' => 'Home content section created successfully.',
            'data' => HomeContentSection::with('items')->find($section->id),
        ], 201);
    }

    /**
     * Update section.
     */
    public function update(StoreHomeContentSectionRequest $request, int $id, AdminHomeContentService $adminService): JsonResponse
    {
        $section = HomeContentSection::findOrFail($id);
        $validated = $request->validated();

        $sectionData = [
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_style' => $validated['display_style'] ?? null,
            'items_per_view' => $validated['items_per_view'] ?? 4,
            'display_limit' => $validated['display_limit'] ?? 10,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'items' => $validated['items'] ?? [],
        ];

        $updated = $adminService->updateSection($section, $sectionData);

        return response()->json([
            'success' => true,
            'message' => 'Home content section updated successfully.',
            'data' => HomeContentSection::with('items')->find($updated->id),
        ]);
    }

    /**
     * Reorder sections.
     */
    public function reorder(ReorderHomeContentSectionsRequest $request, AdminHomeContentService $adminService): JsonResponse
    {
        $orderedIds = $request->input('ordered_ids');
        $adminService->reorderSections($orderedIds);

        return response()->json([
            'success' => true,
            'message' => 'Home content sections reordered successfully.',
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(int $id, AdminHomeContentService $adminService): JsonResponse
    {
        $section = HomeContentSection::findOrFail($id);
        $adminService->toggleSectionStatus($section);
        $section->refresh();

        return response()->json([
            'success' => true,
            'message' => $section->is_active ? 'Section activated successfully.' : 'Section deactivated successfully.',
            'data' => [
                'id' => $section->id,
                'is_active' => (bool) $section->is_active,
            ]
        ]);
    }

    /**
     * Delete section.
     */
    public function destroy(int $id, AdminHomeContentService $adminService): JsonResponse
    {
        $section = HomeContentSection::findOrFail($id);
        $adminService->deleteSection($section);

        return response()->json([
            'success' => true,
            'message' => 'Home content section deleted successfully.',
        ]);
    }
}
