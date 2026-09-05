<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingProductPattern extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_product_patterns';

    protected $fillable = [
        'manufacturing_product_id',
        'name',
        'fabric_width_id',
        'fabric_length',
        'fabric_length_unit',
        'standard_labor_rate',
        'is_default',
    ];

    protected $casts = [
        'fabric_length' => 'decimal:4',
        'standard_labor_rate' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    /**
     * Parent manufacturing product.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }

    /**
     * Selected fabric width from Fabric Width Master.
     */
    public function fabricWidth()
    {
        return $this->belongsTo(FabricWidth::class, 'fabric_width_id');
    }

    /**
     * Task routing sequence associated with this pattern.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'manufacturing_pattern_tasks', 'pattern_id', 'task_id')
            ->withPivot('sequence_number', 'standard_labor_rate', 'is_final_step')
            ->orderByPivot('sequence_number', 'asc')
            ->withTimestamps();
    }
}
