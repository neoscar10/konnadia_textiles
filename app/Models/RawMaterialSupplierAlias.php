<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialSupplierAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'supplier_id',
        'alias_name',
        'supplier_material_code',
    ];

    /**
     * Get the raw material associated with this alias.
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    /**
     * Get the supplier associated with this alias.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
