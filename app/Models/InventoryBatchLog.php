<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_batch_id',
        'user_id',
        'action',
        'quantity',
        'related_production_batch_id',
        'description',
    ];

    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'related_production_batch_id');
    }
}
?>
