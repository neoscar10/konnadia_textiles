<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBaleRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_bale_id',
        'raw_material_id',
        'fabric_width_id',
        'roll_number',
        'design_number',
        'stock_id',
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

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function fabricWidth()
    {
        return $this->belongsTo(FabricWidth::class, 'fabric_width_id');
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
