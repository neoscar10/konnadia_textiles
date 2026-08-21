<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobProductionOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'manufacturing_product_id',
        'task_id',
        'quantity_produced',
        'inventory_batch_id',
        'inventory_bale_roll_id',
        'fabric_width',
        'fabric_length',
        'fabric_width_unit',
        'fabric_length_unit',
        'calculated_base_cost',
        'allocated_wastage_cost',
        'total_fabric_cost',
    ];

    protected $casts = [
        'quantity_produced' => 'integer',
        'inventory_batch_id' => 'integer',
        'inventory_bale_roll_id' => 'integer',
        'fabric_width' => 'decimal:4',
        'fabric_length' => 'decimal:4',
        'calculated_base_cost' => 'decimal:2',
        'allocated_wastage_cost' => 'decimal:2',
        'total_fabric_cost' => 'decimal:2',
    ];

    /**
     * Get the fabric inventory bale roll consumed for this output.
     */
    public function inventoryBaleRoll()
    {
        return $this->belongsTo(InventoryBaleRoll::class, 'inventory_bale_roll_id');
    }

    /**
     * Get the fabric inventory batch consumed for this output.
     */
    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    /**
     * Get the manufacturing product outputted.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get the production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the task stage for this output entry.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
