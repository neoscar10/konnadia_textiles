<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'whatsapp_number',
        'email',
        'portal_access',
        'address',
        'gstin',
        'notes',
    ];

    /**
     * Relationship to InventoryBatches supplied.
     */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'supplier_id');
    }
}
