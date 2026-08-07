<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\InventoryBatchLog;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'batch_number',
        'supplier_name',
        'purchase_date',
        'invoice_number',
        'quantity_received',
        'quantity_consumed',
        'balance_quantity',
        'purchase_rate',
        'total_amount',
        'status',
        // Legacy fields for backward compatibility
        'received_quantity',
        'unit',
        'unit_cost',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity_received' => 'decimal:4',
        'quantity_consumed' => 'decimal:4',
        'balance_quantity' => 'decimal:4',
        'purchase_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        // Legacy
        'received_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate sequential batch number like BAT-YYYY-0001
        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                $year = $batch->purchase_date ? Carbon::parse($batch->purchase_date)->year : now()->year;
                $prefix = "BAT-{$year}-";
                $latest = static::where('batch_number', 'like', "{$prefix}%")
                    ->orderByRaw('CAST(SUBSTRING(batch_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
                    ->first();

                $nextIndex = 1;
                if ($latest) {
                    $parts = explode('-', $latest->batch_number);
                    $lastNum = (int) end($parts);
                    $nextIndex = $lastNum + 1;
                }

                $batch->batch_number = $prefix . str_pad($nextIndex, 4, '0', STR_PAD_LEFT);
            }
        });

        // Sync helper fields for backward compatibility
        static::saving(function ($batch) {
            if ($batch->quantity_received !== null) {
                $batch->received_quantity = (float) $batch->quantity_received;
            } elseif ($batch->received_quantity !== null) {
                $batch->quantity_received = (float) $batch->received_quantity;
            }

            if ($batch->purchase_rate !== null) {
                $batch->unit_cost = (float) $batch->purchase_rate;
            } elseif ($batch->unit_cost !== null) {
                $batch->purchase_rate = (float) $batch->unit_cost;
            }

            if ($batch->balance_quantity !== null && $batch->quantity_received !== null) {
                $batch->quantity_consumed = (float) ($batch->quantity_received - $batch->balance_quantity);
            }
            if ($batch->balance_quantity <= 0) {
                $batch->status = 'depleted';
            }
        });
    }

    /**
     * Get the raw material associated with this batch.
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Get consumptions recorded for this batch.
     */
    public function consumptions()
    {
        return $this->hasMany(JobMaterialConsumption::class);
    }

    /**
     * Get log entries for this batch.
     */
    public function logs()
    {
        return $this->hasMany(InventoryBatchLog::class);
    }

    /**
     * Helper to check if batch is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Deduct quantity from this batch.
     */
    public function deductQuantity(float $amount): void
    {
        $this->quantity_consumed = (float) ($this->quantity_consumed ?? 0) + $amount;
        $this->balance_quantity = max(0.0000, (float) ($this->quantity_received ?? 0) - $this->quantity_consumed);

        if ($this->base_quantity !== null) {
            $unitModel = $this->rawMaterial ? $this->rawMaterial->unitModel : null;
            $baseConsumed = $unitModel ? $unitModel->toBaseQuantity((float) $this->quantity_consumed) : (float) $this->quantity_consumed;
            $this->base_current_balance = max(0.0000, (float) $this->base_quantity - $baseConsumed);
        }

        if ($this->balance_quantity <= 0) {
            $this->status = 'depleted';
        }
        $this->save();
    }

    /**
     * Restore quantity to this batch.
     */
    public function restoreQuantity(float $amount): void
    {
        $this->quantity_consumed = max(0.0000, (float) ($this->quantity_consumed ?? 0) - $amount);
        $this->balance_quantity = min((float) ($this->quantity_received ?? 0), (float) ($this->quantity_received ?? 0) - $this->quantity_consumed);

        if ($this->base_quantity !== null) {
            $unitModel = $this->rawMaterial ? $this->rawMaterial->unitModel : null;
            $baseConsumed = $unitModel ? $unitModel->toBaseQuantity((float) $this->quantity_consumed) : (float) $this->quantity_consumed;
            $this->base_current_balance = min((float) $this->base_quantity, (float) $this->base_quantity - $baseConsumed);
        }

        if ($this->balance_quantity > 0) {
            $this->status = 'active';
        }
        $this->save();
    }

    /**
     * Scope: Filter active batches with positive balance.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('balance_quantity', '>', 0);
    }

    /**
     * Scope: Filter batches by raw material.
     */
    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('raw_material_id', $materialId);
    }
}
