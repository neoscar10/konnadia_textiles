<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionBatchCompleted
{
    use Dispatchable, SerializesModels;

    public $batchId;

    /**
     * Create a new event instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }
}
