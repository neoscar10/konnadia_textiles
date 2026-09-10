<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingPatternFabricWidth extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_pattern_fabric_widths';

    protected $fillable = [
        'pattern_id',
        'fabric_width_id',
        'fabric_length',
        'fabric_length_unit',
    ];

    protected $casts = [
        'fabric_length' => 'decimal:4',
    ];

    public function pattern()
    {
        return $this->belongsTo(ManufacturingProductPattern::class, 'pattern_id');
    }

    public function fabricWidth()
    {
        return $this->belongsTo(FabricWidth::class, 'fabric_width_id');
    }
}
