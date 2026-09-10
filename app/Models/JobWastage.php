<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobWastage extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'manufacturing_product_id',
        'pattern_id',
        'inventory_bale_roll_id',
        'task_id',
        'wastage_type',
        'quantity_wasted',
        'reason',
    ];

    protected $casts = [
        'quantity_wasted' => 'decimal:2',
        'inventory_bale_roll_id' => 'integer',
        'pattern_id' => 'integer',
    ];

    /**
     * Get the inventory bale roll.
     */
    public function inventoryBaleRoll()
    {
        return $this->belongsTo(InventoryBaleRoll::class, 'inventory_bale_roll_id');
    }

    /**
     * Get the manufacturing product (if specific product damaged).
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get the product pattern.
     */
    public function pattern()
    {
        return $this->belongsTo(ManufacturingProductPattern::class, 'pattern_id');
    }

    /**
     * Get the production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the task stage where wastage occurred.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
