<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Fetch the complete category tree recursively.
     */
    public function getTree(): Collection
    {
        $categories = Category::ordered()->get();
        return $this->buildTree($categories);
    }

    /**
     * Helper to build tree structure in memory recursively.
     */
    protected function buildTree(Collection $categories, $parentId = null): Collection
    {
        $branch = collect();
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $category->setRelation('children', $children);
                $branch->add($category);
            }
        }
        return $branch;
    }

    /**
     * Get direct children of a category with optional filters.
     */
    public function getChildren(?int $parentId, array $filters = []): Collection
    {
        $query = Category::where('parent_id', $parentId)->ordered();

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Build breadcrumb path for a category.
     */
    public function getBreadcrumb(?Category $category): array
    {
        $breadcrumbs = [];
        $current = $category;
        
        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Create a new category folder.
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $parentId = empty($data['parent_id']) ? null : (int)$data['parent_id'];
            
            // Check for duplicate name under same parent
            $nameExists = Category::where('parent_id', $parentId)
                ->where('name', trim($data['name']))
                ->exists();
            if ($nameExists) {
                throw new \Exception('Category name already exists in this folder.');
            }

            $slug = $this->generateUniqueSlug($data['name'], $parentId);

            return Category::create([
                'parent_id' => $parentId,
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => empty($data['description']) ? null : trim($data['description']),
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
            ]);
        });
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $parentId = $category->parent_id;
            
            if (isset($data['name']) && trim($data['name']) !== $category->name) {
                // Check name duplicate
                $nameExists = Category::where('parent_id', $parentId)
                    ->where('id', '!=', $category->id)
                    ->where('name', trim($data['name']))
                    ->exists();
                if ($nameExists) {
                    throw new \Exception('Category name already exists in this folder.');
                }

                $data['slug'] = $this->generateUniqueSlug($data['name'], $parentId, $category->id);
                $data['name'] = trim($data['name']);
            }

            if (isset($data['description'])) {
                $data['description'] = empty($data['description']) ? null : trim($data['description']);
            }

            $category->update($data);
            return $category;
        });
    }

    /**
     * Delete a category (soft delete).
     */
    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            if (!$this->canDelete($category)) {
                throw new \Exception('This category has sub-categories. Delete or move them before deleting this category.');
            }
            $category->delete();
        });
    }

    /**
     * Toggle the status of a category.
     */
    public function toggleStatus(Category $category): Category
    {
        return DB::transaction(function () use ($category) {
            $category->is_active = !$category->is_active;
            $category->save();
            return $category;
        });
    }

    /**
     * Check if category can be safely deleted.
     */
    public function canDelete(Category $category): bool
    {
        return $category->children()->count() === 0;
    }

    /**
     * Generate unique slug under the same parent.
     */
    public function generateUniqueSlug(string $name, ?int $parentId, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = Category::where('parent_id', $parentId)
                ->where('slug', $slug);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $counter++;
        }
    }
}
