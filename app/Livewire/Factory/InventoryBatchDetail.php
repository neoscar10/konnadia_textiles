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
    public bool $showMismatchConfirmationModal = false;
    public ?int $activeBaleIdToOpen = null;
    public $baleRollCount = '';
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
        $this->baleRollCount = '';
        $this->baleRollLengths = [];
        $this->baleMismatchWarning = null;
        $this->showMismatchConfirmationModal = false;

        $this->showOpenBaleModal = true;
    }

    public function updatedBaleRollCount($count)
    {
        if ($count === '' || $count === null || intval($count) <= 0) {
            $this->baleRollLengths = [];
            $this->baleMismatchWarning = null;
            return;
        }

        $count = max(1, min(50, intval($count)));
        $this->baleRollCount = $count;

        $currentCount = count($this->baleRollLengths);
        if ($currentCount < $count) {
            for ($i = $currentCount; $i < $count; $i++) {
                $this->baleRollLengths[$i] = '';
            }
        } else if ($currentCount > $count) {
            $this->baleRollLengths = array_slice($this->baleRollLengths, 0, $count);
        }

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

        $filledLengths = array_filter($this->baleRollLengths, fn($val) => $val !== '' && $val !== null);
        if (empty($filledLengths)) {
            $this->baleMismatchWarning = null;
            return;
        }

        $sum = array_sum(array_map('floatval', $filledLengths));
        $declared = (float) $bale->declared_length;

        if (abs($sum - $declared) > 0.001) {
            $diff = round($sum - $declared, 2);
            $sign = $diff > 0 ? "+{$diff}" : "{$diff}";
            $this->baleMismatchWarning = "Warning: Total measured roll length ({$sum}m) differs from declared purchase bale length ({$declared}m) by {$sign}m. This measured length ({$sum}m) will override the declared length for material calculations.";
        } else {
            $this->baleMismatchWarning = null;
        }
    }

    public function submitOpenedBaleForm()
    {
        if (!$this->activeBaleIdToOpen) return;

        if (empty($this->baleRollCount) || count($this->baleRollLengths) < 1) {
            $this->addError('baleRollCount', 'Please enter the number of rolls in the bale.');
            return;
        }

        foreach ($this->baleRollLengths as $i => $len) {
            if ($len === '' || $len === null || (float)$len <= 0) {
                $this->addError("baleRollLengths.{$i}", "Please enter a valid length for Roll #" . ($i + 1));
                return;
            }
        }

        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);
        $sum = array_sum(array_map('floatval', $this->baleRollLengths));
        $declared = (float) $bale->declared_length;

        if (abs($sum - $declared) > 0.001 && !$this->showMismatchConfirmationModal) {
            $this->showMismatchConfirmationModal = true;
            return;
        }

        $this->saveOpenedBale();
    }

    public function saveOpenedBale()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);

        $result = $bale->openBale($this->baleRollLengths);
        $this->showOpenBaleModal = false;
        $this->showMismatchConfirmationModal = false;
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

        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded for stock calculations.", type: 'success');
    }

    public function render()
    {
        return view('livewire.factory.inventory-batch-detail');
    }
}
?>
