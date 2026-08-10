<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBaleRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_bale_id',
        'roll_number',
        'initial_length',
        'current_balance_length',
        'status', // active, depleted
    ];

    protected $casts = [
        'initial_length' => 'float',
        'current_balance_length' => 'float',
    ];

    public function bale()
    {
        return $this->belongsTo(InventoryBale::class, 'inventory_bale_id');
    }

    /**
     * Deduct cut length from this roll.
     */
    public function deductLength(float $length)
    {
        $newBalance = max(0, (float) $this->current_balance_length - $length);
        $status = $newBalance <= 0 ? 'depleted' : 'active';
        $this->update([
            'current_balance_length' => $newBalance,
            'status' => $status,
        ]);

        // Also update parent bale balance
        if ($this->bale) {
            $this->bale->deductLength($length);
        }
    }
}
