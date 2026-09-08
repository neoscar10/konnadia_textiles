<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactorySupervisor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'department',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supervisor) {
            if (empty($supervisor->code)) {
                $latestId = static::withTrashed()->max('id') ?? 0;
                $supervisor->code = 'SUP-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Production batches supervised by this supervisor.
     */
    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class, 'factory_supervisor_id');
    }

    /**
     * Scope to only active supervisors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
