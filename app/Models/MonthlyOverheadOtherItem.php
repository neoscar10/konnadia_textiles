<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyOverheadOtherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_overhead_allocation_id',
        'category_name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(MonthlyOverheadAllocation::class, 'monthly_overhead_allocation_id');
    }
}
