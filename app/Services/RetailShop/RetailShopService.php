<?php

namespace App\Services\RetailShop;

use App\Models\RetailShop;
use Illuminate\Support\Str;

class RetailShopService
{
    /**
     * List retail shops with search and filters.
     */
    public function list(array $filters = [], int $perPage = 10)
    {
        $query = RetailShop::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('shop_code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = $filters['status'] === 'active';
            $query->where('is_active', $isActive);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new retail shop.
     */
    public function create(array $data): RetailShop
    {
        if (empty($data['shop_code'])) {
            $data['shop_code'] = $this->generateShopCode();
        }

        return RetailShop::create($data);
    }

    /**
     * Update an existing retail shop.
     */
    public function update(RetailShop $shop, array $data): RetailShop
    {
        $shop->update($data);
        return $shop;
    }

    /**
     * Delete a retail shop (soft deletes).
     */
    public function delete(RetailShop $shop): bool
    {
        return $shop->delete();
    }

    /**
     * Toggle active status of a retail shop.
     */
    public function toggleStatus(RetailShop $shop): RetailShop
    {
        $shop->is_active = !$shop->is_active;
        $shop->save();
        return $shop;
    }

    /**
     * Generate the next sequential shop code (e.g., SHOP-001, SHOP-002).
     */
    public function generateShopCode(): string
    {
        $lastShop = RetailShop::withTrashed()
            ->where('shop_code', 'like', 'SHOP-%')
            ->orderBy('shop_code', 'desc')
            ->first();

        if (!$lastShop) {
            return 'SHOP-001';
        }

        $lastNumber = intval(Str::after($lastShop->shop_code, 'SHOP-'));
        $newNumber = $lastNumber + 1;

        return 'SHOP-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
