<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    protected CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define hierarchy structure
        $hierarchy = [
            [
                'name' => "Men's Wear",
                'description' => 'A wide range of shirts, t-shirts, and apparel for men.',
                'children' => [
                    [
                        'name' => 'Shirts',
                        'description' => 'Formal and casual shirts for all occasions.',
                        'children' => [
                            ['name' => 'Formal Shirts', 'description' => 'Premium formal shirts for office and corporate wear.'],
                            ['name' => 'Casual Shirts', 'description' => 'Trendy casual shirts.'],
                        ]
                    ],
                    [
                        'name' => 'T-Shirts',
                        'description' => 'Polo and crew neck t-shirts.',
                    ]
                ]
            ],
            [
                'name' => "Women's Wear",
                'description' => 'Ethnic and modern collection for women.',
                'children' => [
                    [
                        'name' => 'Sarees',
                        'description' => 'Traditional and designer sarees.',
                    ],
                    [
                        'name' => 'Kurtis',
                        'description' => 'Comfortable and stylish kurtis.',
                    ]
                ]
            ],
            [
                'name' => 'Kids Wear',
                'description' => 'Kids clothing range.',
                'children' => [
                    ['name' => 'Boys', 'description' => 'Apparel for boys.'],
                    ['name' => 'Girls', 'description' => 'Apparel for girls.'],
                ]
            ]
        ];

        foreach ($hierarchy as $sortOrder => $rootItem) {
            $this->createCategoryRecursively($rootItem, null, $sortOrder);
        }
    }

    protected function createCategoryRecursively(array $item, ?int $parentId = null, int $sortOrder = 0): void
    {
        $category = Category::where('parent_id', $parentId)
            ->where('name', trim($item['name']))
            ->first();

        if (!$category) {
            $category = $this->service->create([
                'parent_id' => $parentId,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        if (!empty($item['children'])) {
            foreach ($item['children'] as $childSortOrder => $childItem) {
                $this->createCategoryRecursively($childItem, $category->id, $childSortOrder);
            }
        }
    }
}
