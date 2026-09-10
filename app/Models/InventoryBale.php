<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBale extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_batch_id',
        'bale_number',
        'item_name',
        'design_number',
        'stock_id',
        'status', // unopened, opened, depleted
        'declared_length',
        'cost_per_unit',
        'total_cost',
        'photo_path',
        'actual_recorded_length',
        'current_balance_length',
        'roll_count',
    ];

    protected $casts = [
        'declared_length' => 'float',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'actual_recorded_length' => 'float',
        'current_balance_length' => 'float',
        'roll_count' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function rolls()
    {
        return $this->hasMany(InventoryBaleRoll::class, 'inventory_bale_id');
    }

    public function activeRolls()
    {
        return $this->hasMany(InventoryBaleRoll::class, 'inventory_bale_id')->where('status', 'active');
    }

    /**
     * Open an unopened bale by recording roll count and individual roll details.
     */
    public function openBale(array $rollLengths): array
    {
        $sum = 0;
        $rolls = [];

        foreach ($rollLengths as $index => $item) {
            if (is_array($item)) {
                $length = (float) ($item['length'] ?? 0);
                $matId  = !empty($item['raw_material_id']) ? (int) $item['raw_material_id'] : ($this->batch?->raw_material_id);
                $fwId   = !empty($item['fabric_width_id']) ? (int) $item['fabric_width_id'] : null;
                $design = !empty($item['design_number']) ? trim($item['design_number']) : ($this->design_number);
                $stock  = !empty($item['stock_id']) ? trim($item['stock_id']) : ($this->stock_id);
            } else {
                $length = (float) $item;
                $matId  = $this->batch?->raw_material_id;
                $fwId   = null;
                $design = $this->design_number;
                $stock  = $this->stock_id;
            }

            $sum += $length;
            $rolls[] = $this->rolls()->create([
                'raw_material_id'        => $matId,
                'fabric_width_id'        => $fwId,
                'roll_number'            => "Roll " . ($index + 1),
                'design_number'          => $design,
                'stock_id'               => $stock,
                'initial_length'         => $length,
                'current_balance_length' => $length,
                'status'                 => 'active',
            ]);
        }

        $diff = $sum - (float) $this->declared_length;

        $this->update([
            'status'                 => 'opened',
            'roll_count'             => count($rollLengths),
            'actual_recorded_length' => $sum,
            'current_balance_length' => $sum,
        ]);

        // Sync sibling bales with same bale_number
        $siblingBales = static::where('bale_number', $this->bale_number)
            ->where('id', '!=', $this->id)
            ->where('status', 'unopened')
            ->get();

        foreach ($siblingBales as $sb) {
            $sbMatId = $sb->batch?->raw_material_id;
            $sbSum = 0;
            $sbRollsCount = 0;

            foreach ($rolls as $r) {
                if ($r->raw_material_id == $sbMatId && $sb->rolls()->where('id', $r->id)->doesntExist()) {
                    $sbSum += $r->initial_length;
                    $sbRollsCount++;
                }
            }

            $sb->update([
                'status'                 => 'opened',
                'roll_count'             => max(1, $sbRollsCount),
                'actual_recorded_length' => $sbSum > 0 ? $sbSum : $sb->declared_length,
                'current_balance_length' => $sbSum > 0 ? $sbSum : $sb->declared_length,
            ]);
        }

        if (abs($diff) > 0.0001 && $this->batch) {
            $this->batch->balance_quantity = max(0, (float) $this->batch->balance_quantity + $diff);
            $this->batch->quantity_received = max(0, (float) $this->batch->quantity_received + $diff);
            $this->batch->save();

            \App\Services\InventoryBatchLogger::log(
                $this->batch->id,
                'adjusted',
                $diff,
                null,
                "Bale {$this->bale_number} opened: stock adjusted by {$diff}m based on actual measured rolls length ({$sum}m vs declared {$this->declared_length}m)"
            );
        }

        return [
            'total_recorded_length' => $sum,
            'declared_length'       => (float) $this->declared_length,
            'difference'            => round($diff, 4),
            'has_mismatch'          => abs($diff) > 0.001,
        ];
    }

    /**
     * Deduct length from this bale's balance.
     */
    public function deductLength(float $length)
    {
        $newBalance = max(0, (float) $this->current_balance_length - $length);
        $status = $newBalance <= 0 ? 'depleted' : 'opened';
        $this->update([
            'current_balance_length' => $newBalance,
            'status' => $status,
        ]);
    }
}
