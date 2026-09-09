<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyOverheadAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'period_date',
        'production_value',
        'stitching_material_total',
        'salaried_staff_total',
        'other_overheads_total',
        'total_overhead',
        'overhead_percentage',
        'status',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'period_date' => 'date',
        'production_value' => 'decimal:2',
        'stitching_material_total' => 'decimal:2',
        'salaried_staff_total' => 'decimal:2',
        'other_overheads_total' => 'decimal:2',
        'total_overhead' => 'decimal:2',
        'overhead_percentage' => 'decimal:2',
    ];

    public function materialItems()
    {
        return $this->hasMany(MonthlyOverheadMaterialItem::class, 'monthly_overhead_allocation_id');
    }

    public function otherItems()
    {
        return $this->hasMany(MonthlyOverheadOtherItem::class, 'monthly_overhead_allocation_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPeriodFormattedAttribute(): string
    {
        if ($this->period_date) {
            return $this->period_date->format('M Y');
        }
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('M Y');
    }
}
