<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaborCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public static function generateNextCode(): string
    {
        $latestId = static::max('id') ?? 0;
        $candidate = 'LCAT-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
        
        while (static::where('code', $candidate)->exists()) {
            $latestId++;
            $candidate = 'LCAT-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = static::generateNextCode();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'labor_category_id');
    }

    public function labors()
    {
        return $this->hasMany(Labor::class, 'labor_category_id');
    }
}
