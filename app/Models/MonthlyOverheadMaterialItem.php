<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyOverheadMaterialItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_overhead_allocation_id',
        'raw_material_id',
        'opening_stock_value',
        'purchases_value',
        'closing_stock_qty',
        'closing_stock_value',
        'consumed_cost',
    ];

    protected $casts = [
        'opening_stock_value' => 'decimal:2',
        'purchases_value' => 'decimal:2',
        'closing_stock_qty' => 'decimal:2',
        'closing_stock_value' => 'decimal:2',
        'consumed_cost' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(MonthlyOverheadAllocation::class, 'monthly_overhead_allocation_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
