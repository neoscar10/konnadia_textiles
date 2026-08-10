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
        'status', // unopened, opened, depleted
        'declared_length',
        'actual_recorded_length',
        'current_balance_length',
        'roll_count',
    ];

    protected $casts = [
        'declared_length' => 'float',
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
     * Open an unopened bale by recording roll count and individual roll lengths.
     */
    public function openBale(array $rollLengths): array
    {
        $sum = 0;
        $rolls = [];

        foreach ($rollLengths as $index => $length) {
            $length = (float) $length;
            $sum += $length;
            $rolls[] = $this->rolls()->create([
                'roll_number' => "Roll " . ($index + 1),
                'initial_length' => $length,
                'current_balance_length' => $length,
                'status' => 'active',
            ]);
        }

        $this->update([
            'status' => 'opened',
            'roll_count' => count($rollLengths),
            'actual_recorded_length' => $sum,
            'current_balance_length' => $sum,
        ]);

        return [
            'total_recorded_length' => $sum,
            'declared_length' => (float) $this->declared_length,
            'difference' => round($sum - (float) $this->declared_length, 4),
            'has_mismatch' => abs($sum - (float) $this->declared_length) > 0.001,
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
