<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationValue;
use App\Models\ProductCombination;
use Illuminate\Support\Facades\DB;

class ProductVariationService
{
    /**
     * Sync variation groups and values for a product.
     */
    public function syncVariationGroups(Product $product, array $groups): void
    {
        DB::transaction(function () use ($product, $groups) {
            // Keep track of active groups/values to delete removed ones
            $activeGroupIds = [];
            $activeValueIds = [];

            foreach ($groups as $sortOrder => $groupData) {
                if (empty($groupData['name'])) continue;

                $group = ProductVariationGroup::updateOrCreate([
                    'product_id' => $product->id,
                    'name' => trim($groupData['name']),
                ], [
                    'display_type' => $groupData['display_type'] ?? 'text',
                    'has_images' => !empty($groupData['has_images']),
                    'sort_order' => $sortOrder,
                ]);

                $activeGroupIds[] = $group->id;

                foreach ($groupData['values'] ?? [] as $valSortOrder => $valData) {
                    if (empty($valData['value'])) continue;

                    $value = ProductVariationValue::updateOrCreate([
                        'product_variation_group_id' => $group->id,
                        'value' => trim($valData['value']),
                    ], [
                        'color_hex' => empty($valData['color_hex']) ? null : trim($valData['color_hex']),
                        'is_default' => !empty($valData['is_default']),
                        'sort_order' => $valSortOrder,
                    ]);

                    $activeValueIds[] = $value->id;

                    // Sync value media
                    $valueMediaPaths = $valData['media'] ?? [];
                    $value->media()->whereNotIn('file_path', $valueMediaPaths)->delete();
                    foreach ($valueMediaPaths as $mSortOrder => $filePath) {
                        $value->media()->updateOrCreate([
                            'file_path' => $filePath,
                        ], [
                            'sort_order' => $mSortOrder,
                        ]);
                    }
                }
            }

            // Cleanup removed values & groups
            ProductVariationGroup::where('product_id', $product->id)
                ->whereNotIn('id', $activeGroupIds)
                ->delete();

            // Cleanup values for remaining groups
            ProductVariationValue::whereIn('product_variation_group_id', $activeGroupIds)
                ->whereNotIn('id', $activeValueIds)
                ->delete();
        });
    }

    /**
     * Generate Cartesian Product of variation combinations.
     */
    public function generateCombinations(array $groups): array
    {
        if (empty($groups)) {
            return [];
        }

        $arrays = [];
        foreach ($groups as $group) {
            if (empty($group['name'])) continue;
            $groupName = $group['name'];
            $values = [];
            foreach ($group['values'] ?? [] as $val) {
                if (isset($val['value']) && trim($val['value']) !== '') {
                    $values[] = [
                        'group_name' => $groupName,
                        'value' => trim($val['value'])
                    ];
                }
            }
            if (!empty($values)) {
                $arrays[] = $values;
            }
        }

        if (empty($arrays)) {
            return [];
        }

        $results = [[]];
        foreach ($arrays as $values) {
            $temp = [];
            foreach ($results as $result) {
                foreach ($values as $value) {
                    $temp[] = array_merge($result, [$value['group_name'] => $value['value']]);
                }
            }
            $results = $temp;
        }

        $finalCombinations = [];
        foreach ($results as $res) {
            $finalCombinations[] = [
                'combination_values' => $res,
                'sku' => null,
                'price' => null,
                'stock_quantity' => '',
                'is_active' => true
            ];
        }

        return $finalCombinations;
    }

    /**
     * Preserve stock/price data from existing database records.
     */
    public function preserveExistingCombinationData(Product $product, array $newCombinations): array
    {
        $existing = $product->combinations()->get();

        foreach ($newCombinations as &$newComb) {
            $newVals = $newComb['combination_values'];

            foreach ($existing as $ext) {
                $extVals = $ext->combination_values;
                if (count($newVals) === count($extVals) && !array_diff_assoc($newVals, $extVals)) {
                    $newComb['sku'] = $ext->sku;
                    $newComb['price'] = $ext->price !== null ? (float)$ext->price : null;
                    $newComb['stock_quantity'] = $ext->stock_quantity === null ? '' : (int)$ext->stock_quantity;
                    $newComb['is_active'] = (bool)$ext->is_active;
                    break;
                }
            }
        }

        return $newCombinations;
    }

    /**
     * Sync combination values list in database.
     */
    public function syncCombinations(Product $product, array $combinations): void
    {
        DB::transaction(function () use ($product, $combinations) {
            // Remove existing combinations
            ProductCombination::where('product_id', $product->id)->delete();

            foreach ($combinations as $comb) {
                $sku = empty($comb['sku']) ? null : trim($comb['sku']);
                if (empty($sku)) {
                    $suffix = collect($comb['combination_values'])
                        ->map(fn($v) => \Illuminate\Support\Str::slug($v))
                        ->implode('-');
                    $sku = strtoupper($product->sku . '-' . $suffix);
                }

                ProductCombination::create([
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'combination_values' => $comb['combination_values'],
                    'price' => (isset($comb['price']) && trim($comb['price']) !== '') ? (float)$comb['price'] : null,
                    'stock_quantity' => (isset($comb['stock_quantity']) && $comb['stock_quantity'] !== '') ? (int)$comb['stock_quantity'] : null,
                    'is_active' => isset($comb['is_active']) ? (bool)$comb['is_active'] : true,
                ]);
            }
        });
    }
}
