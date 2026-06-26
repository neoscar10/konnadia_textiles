<?php

namespace App\Services\Home;

use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminHomeContentService
{
    /**
     * Create a new homepage content section with optional items.
     */
    public function createSection(array $data): HomeContentSection
    {
        return DB::transaction(function () use ($data) {
            $maxSort = HomeContentSection::max('sort_order') ?? 0;
            
            $section = HomeContentSection::create([
                'type' => $data['type'],
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $maxSort + 1,
                'display_style' => $data['display_style'] ?? null,
                'items_per_view' => $data['items_per_view'] ?? null,
                'display_limit' => $data['display_limit'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'settings' => $data['settings'] ?? null,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $itemData) {
                    $section->items()->create(array_merge($itemData, [
                        'sort_order' => $index,
                    ]));
                }
            }

            $this->clearCache();

            return $section;
        });
    }

    /**
     * Update an existing content section.
     */
    public function updateSection(HomeContentSection $section, array $data): HomeContentSection
    {
        return DB::transaction(function () use ($section, $data) {
            $section->update([
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'display_style' => $data['display_style'] ?? null,
                'items_per_view' => $data['items_per_view'] ?? null,
                'display_limit' => $data['display_limit'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'settings' => $data['settings'] ?? null,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $section->items()->delete();
                
                foreach ($data['items'] as $index => $itemData) {
                    $section->items()->create(array_merge($itemData, [
                        'sort_order' => $index,
                    ]));
                }
            }

            $this->clearCache();

            return $section->fresh();
        });
    }

    /**
     * Reorder sections.
     */
    public function reorderSections(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                HomeContentSection::where('id', $id)->update([
                    'sort_order' => $index,
                ]);
            }
            $this->clearCache();
        });
    }

    /**
     * Toggle status.
     */
    public function toggleSectionStatus(HomeContentSection $section): void
    {
        $section->update(['is_active' => !$section->is_active]);
        $this->clearCache();
    }

    /**
     * Delete section.
     */
    public function deleteSection(HomeContentSection $section): void
    {
        DB::transaction(function () use ($section) {
            $section->items()->delete();
            $section->delete();
            $this->clearCache();
        });
    }

    /**
     * Clear home page rendering caches.
     */
    protected function clearCache(): void
    {
        Cache::forget('home_content_sections_active');
    }
}
