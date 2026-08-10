<?php

namespace App\Livewire\Factory;

use App\Models\InventoryBatch;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class InventoryBatchDetail extends Component
{
    public InventoryBatch $batch;

    // Unopened Bale Modal State
    public bool $showOpenBaleModal = false;
    public ?int $activeBaleIdToOpen = null;
    public int $baleRollCount = 5;
    public array $baleRollLengths = [];
    public ?string $baleMismatchWarning = null;

    public function mount(InventoryBatch $batch)
    {
        // Eager load related data: raw material, unit models, consumptions, stage executions, logs, bales, and rolls
        $this->batch = $batch->load([
            'rawMaterial.category',
            'rawMaterial.unitGroup',
            'rawMaterial.unitModel',
            'consumptions.job.manufacturingProduct',
            'logs.user',
            'bales.rolls',
        ]);
    }

    public function triggerOpenBaleModal(int $baleId)
    {
        $bale = \App\Models\InventoryBale::with('batch')->findOrFail($baleId);
        $this->activeBaleIdToOpen = $bale->id;
        $this->baleRollCount = 5;
        $this->baleMismatchWarning = null;

        $declaredPerRoll = round((float) $bale->declared_length / 5, 2);
        $this->baleRollLengths = array_fill(0, 5, $declaredPerRoll);

        $this->showOpenBaleModal = true;
    }

    public function updatedBaleRollCount($count)
    {
        $count = max(1, min(50, intval($count)));
        $this->baleRollCount = $count;

        $bale = \App\Models\InventoryBale::find($this->activeBaleIdToOpen);
        $declaredPerRoll = $bale ? round((float) $bale->declared_length / $count, 2) : 100;
        $this->baleRollLengths = array_fill(0, $count, $declaredPerRoll);
        $this->checkBaleMismatchWarning();
    }

    public function updatedBaleRollLengths()
    {
        $this->checkBaleMismatchWarning();
    }

    protected function checkBaleMismatchWarning()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::find($this->activeBaleIdToOpen);
        if (!$bale) return;

        $sum = array_sum(array_map('floatval', $this->baleRollLengths));
        $declared = (float) $bale->declared_length;

        if (abs($sum - $declared) > 0.001) {
            $diff = round($sum - $declared, 2);
            $this->baleMismatchWarning = "Notice: Sum of roll lengths ({$sum}m) differs from declared purchase bale length ({$declared}m) by {$diff}m. Measured roll total ({$sum}m) will be recorded as actual stock length.";
        } else {
            $this->baleMismatchWarning = null;
        }
    }

    public function saveOpenedBale()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);

        $result = $bale->openBale($this->baleRollLengths);
        $this->showOpenBaleModal = false;
        $this->activeBaleIdToOpen = null;

        $this->batch->refresh();
        $this->batch->load([
            'rawMaterial.category',
            'rawMaterial.unitGroup',
            'rawMaterial.unitModel',
            'consumptions.job.manufacturingProduct',
            'logs.user',
            'bales.rolls',
        ]);

        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded.", type: 'success');
    }

    public function render()
    {
        return view('livewire.factory.inventory-batch-detail');
    }
}
?>
