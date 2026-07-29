<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get materials belonging to this category.
     */
    public function materials()
    {
        return $this->hasMany(RawMaterial::class, 'raw_material_category_id');
    }

    /**
     * Get tasks allowed to consume materials in this category.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_raw_material_category');
    }
}
