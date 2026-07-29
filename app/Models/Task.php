<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function labors()
    {
        return $this->belongsToMany(Labor::class);
    }

    public function manufacturingProducts()
    {
        return $this->belongsToMany(ManufacturingProduct::class, 'manufacturing_product_task')
                    ->withPivot('standard_labor_rate')
                    ->withTimestamps();
    }

    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class);
    }

    public function rawMaterialCategories()
    {
        return $this->belongsToMany(RawMaterialCategory::class, 'task_raw_material_category');
    }
}
