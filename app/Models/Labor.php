<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Labor extends Model
{
    protected $fillable = [
        'name', 'code', 'mobile_number', 'status', 'payment_method', 'monthly_salary'
    ];

    protected $casts = [
        'status' => 'boolean',
        'monthly_salary' => 'decimal:2',
    ];

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($labor) {
            if (empty($labor->code)) {
                $latestId = static::max('id') ?? 0;
                $labor->code = 'LAB-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
