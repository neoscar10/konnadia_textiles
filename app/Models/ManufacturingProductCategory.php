<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ManufacturingProductCategory
 *
 * Separate from the e-commerce Category model — this governs the
 * factory floor product classification used in manufacturing workflows.
 */
class ManufacturingProductCategory extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_product_categories';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Manufacturing products that belong to this category.
     */
    public function manufacturingProducts()
    {
        return $this->hasMany(ManufacturingProduct::class, 'manufacturing_product_category_id');
    }

    /**
     * Default task routing sequence for products created in this category.
     */
    public function defaultTasks()
    {
        return $this->belongsToMany(Task::class, 'manufacturing_category_tasks', 'manufacturing_product_category_id', 'task_id')
            ->withPivot('sequence_number', 'standard_labor_rate', 'is_final_step')
            ->orderByPivot('sequence_number', 'asc')
            ->withTimestamps();
    }

    /**
     * Scope to only return active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
