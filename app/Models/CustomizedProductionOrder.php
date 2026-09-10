<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizedProductionOrder extends Model
{
    use HasFactory;

    protected $table = 'customized_production_orders';

    protected $fillable = [
        'custom_order_id',
        'item_description',
        'raw_material_id',
        'fabric_name',
        'width',
        'length',
        'length_unit',
        'target_quantity',
        'status',
        'notes',
        'production_job_id',
        'created_by',
    ];

    protected $casts = [
        'width' => 'float',
        'length' => 'float',
        'target_quantity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->custom_order_id)) {
                $year = date('Y');
                $maxNum = static::where('custom_order_id', 'like', "CUST-PROD-{$year}-%")
                    ->get()
                    ->map(fn($o) => (int) str_replace("CUST-PROD-{$year}-", '', $o->custom_order_id))
                    ->max() ?: 0;

                $order->custom_order_id = sprintf("CUST-PROD-%s-%04d", $year, $maxNum + 1);
            }
        });
    }

    /**
     * Get the fabric raw material model.
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    /**
     * Get the linked production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the user who created this custom order.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor for formatted dimensions e.g. "108 × 120 Inch".
     */
    public function getDimensionsFormattedAttribute(): string
    {
        $w = (float) $this->width;
        $l = (float) $this->length;
        $unit = $this->length_unit ?: 'Inch';

        // Format whole numbers cleanly
        $wStr = ($w == (int)$w) ? (string)(int)$w : (string)$w;
        $lStr = ($l == (int)$l) ? (string)(int)$l : (string)$l;

        return "{$wStr} × {$lStr} {$unit}";
    }

    /**
     * Count of configured dynamic task stages on linked production job.
     */
    public function getConfiguredTasksCountAttribute(): int
    {
        if ($this->productionJob) {
            return $this->productionJob->stageExecutions()->count();
        }
        return 0;
    }
}
