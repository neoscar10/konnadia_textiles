<?php

namespace App\Services\Customer;

use App\Models\CustomerLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerLevelService
{
    /**
     * List customer levels with optional filtering and pagination.
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = CustomerLevel::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . trim($filters['search']) . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->ordered()->paginate($perPage);
    }

    /**
     * Create a new customer level.
     */
    public function create(array $data): CustomerLevel
    {
        return DB::transaction(function () use ($data) {
            $data['name'] = trim($data['name']);
            return CustomerLevel::create($data);
        });
    }

    /**
     * Update an existing customer level.
     */
    public function update(CustomerLevel $level, array $data): CustomerLevel
    {
        return DB::transaction(function () use ($level, $data) {
            if (isset($data['name'])) {
                $data['name'] = trim($data['name']);
            }
            
            $level->update($data);
            return $level;
        });
    }

    /**
     * Delete a customer level.
     */
    public function delete(CustomerLevel $level): void
    {
        DB::transaction(function () use ($level) {
            // Future check: throw exception if customers are assigned to this level.
            // if ($level->customers()->exists()) {
            //     throw new \Exception('Cannot delete a customer level that has customers assigned.');
            // }

            $level->delete();
        });
    }

    /**
     * Toggle the active status of a customer level.
     */
    public function toggleStatus(CustomerLevel $level): CustomerLevel
    {
        return DB::transaction(function () use ($level) {
            $level->is_active = !$level->is_active;
            $level->save();
            return $level;
        });
    }
}
