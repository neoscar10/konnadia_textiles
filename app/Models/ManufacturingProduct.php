<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'standard_labor_rate',
        'manufacturing_product_category_id',
        'status',
        'image_path',
        'is_fabric_used',
        'standard_fabric_width',
        'standard_fabric_length',
        'fabric_width_unit',
        'fabric_length_unit',
        'is_subsidiary_used',
        'is_stitching_used',
        'is_packaging_used',
        'product_id',
        'product_combination_id',
    ];

    protected $casts = [
        'standard_labor_rate' => 'decimal:2',
        'is_fabric_used' => 'boolean',
        'standard_fabric_width' => 'decimal:4',
        'standard_fabric_length' => 'decimal:4',
        'is_subsidiary_used' => 'boolean',
        'is_stitching_used' => 'boolean',
        'is_packaging_used' => 'boolean',
        'product_id' => 'integer',
        'product_combination_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $year = date('Y');
                $latest = static::where('code', 'like', "MP-{$year}-%")->latest('id')->first();
                $seq = 1;
                if ($latest) {
                    $parts = explode('-', $latest->code);
                    $seq = ((int) end($parts)) + 1;
                } else {
                    $latestId = static::max('id') ?? 0;
                    $seq = $latestId + 1;
                }
                $product->code = "MP-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the manufacturing product category this product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ManufacturingProductCategory::class, 'manufacturing_product_category_id');
    }

    /**
     * Patterns/variations associated with this manufacturing product.
     */
    public function patterns()
    {
        return $this->hasMany(ManufacturingProductPattern::class, 'manufacturing_product_id');
    }

    /**
     * Get default pattern for this product.
     */
    public function defaultPattern()
    {
        return $this->hasOne(ManufacturingProductPattern::class, 'manufacturing_product_id')->where('is_default', true);
    }

    /**
     * Tasks associated with this manufacturing product, ordered by routing sequence.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'manufacturing_product_task')
                    ->withPivot('standard_labor_rate', 'sequence_number', 'is_final_step')
                    ->orderByPivot('sequence_number', 'asc')
                    ->withTimestamps();
    }

    /**
     * Return the first task in this product's routing sequence.
     */
    public function getFirstTask(): ?Task
    {
        return $this->tasks()->first();
    }

    /**
     * Return the final step task for this product's routing.
     */
    public function getFinalTask(): ?Task
    {
        return $this->tasks()->wherePivot('is_final_step', true)->first();
    }

    /**
     * Allocations associated with this manufacturing product.
     */
    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class);
    }

    /**
     * Retrieve the Standard Labor Rate for a given Task.
     * Looks up the pivot table rate first, falling back to the product's base standard_labor_rate.
     *
     * @param int $taskId
     * @return float|null
     */
    public function getStandardLaborRateForTask($taskId): ?float
    {
        $task = $this->tasks()->where('task_id', $taskId)->first();

        if ($task && !is_null($task->pivot->standard_labor_rate)) {
            return (float) $task->pivot->standard_labor_rate;
        }

        return !is_null($this->standard_labor_rate) ? (float) $this->standard_labor_rate : null;
    }

    /**
     * Retrieve the next task in the routing sequence for this manufacturing product,
     * or return null if current task is marked as the final step or no further task exists.
     *
     * @param int $currentTaskId
     * @return Task|null
     */
    public function getNextTask($currentTaskId): ?Task
    {
        $orderedTasks = $this->tasks;
        if ($orderedTasks->isEmpty()) {
            $orderedTasks = Task::where('status', true)->get();
        }

        $currentIndex = null;
        $currentTaskObj = null;

        foreach ($orderedTasks as $idx => $task) {
            if ($task->id == $currentTaskId) {
                $currentIndex = $idx;
                $currentTaskObj = $task;
                break;
            }
        }

        // If current task is designated as final step, stop workflow
        if ($currentTaskObj && isset($currentTaskObj->pivot) && $currentTaskObj->pivot->is_final_step) {
            return null;
        }

        if ($currentIndex !== null && isset($orderedTasks[$currentIndex + 1])) {
            return $orderedTasks[$currentIndex + 1];
        }

        return null;
    }

    /**
     * Get the subsidiary materials configured for this product.
     */
    public function subsidiaryMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'manufacturing_product_subsidiary_materials')
            ->withPivot('consumption_quantity')
            ->withTimestamps();
    }

    /**
     * Get the stitching materials configured for this product.
     */
    public function stitchingMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'manufacturing_product_stitching_materials')
            ->withTimestamps();
    }

    /**
     * Get the packaging materials configured for this product.
     */
    public function packagingMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'manufacturing_product_packaging_materials')
            ->withPivot('required_quantity')
            ->withTimestamps();
    }

    /**
     * Map to storefront frontend product.
     */
    public function frontendProduct()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Map to storefront frontend variant combination.
     */
    public function frontendCombination()
    {
        return $this->belongsTo(ProductCombination::class, 'product_combination_id');
    }

    /**
     * Check if product is mapped to a storefront entity.
     */
    public function hasStorefrontMapping(): bool
    {
        return !empty($this->product_id) || !empty($this->product_combination_id);
    }

    /**
     * Retrieve the mapped storefront Product or ProductCombination entity.
     */
    public function getMappedStorefrontEntity()
    {
        if (!empty($this->product_combination_id)) {
            return $this->frontendCombination;
        }
        if (!empty($this->product_id)) {
            return $this->frontendProduct;
        }
        return null;
    }
}
