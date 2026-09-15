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
        // Eager load related data: raw material, unit models, consumptions, stage executions, logs, bales, baleItems, and rolls
        $this->batch = $batch->load([
            'rawMaterial.category',
            'rawMaterial.unitGroup',
            'rawMaterial.unitModel',
            'consumptions.job.manufacturingProduct',
            'logs.user',
            'bales.baleItems.rawMaterial',
            'bales.rolls.rawMaterial',
        ]);
    }

    public function getAvailableWidthsForMaterial($rawMaterialId)
    {
        if (!$rawMaterialId) return collect();
        $mat = \App\Models\RawMaterial::with('fabricWidths.unitModel')->find($rawMaterialId);
        return $mat ? $mat->available_widths : collect();
    }

    public function getAvailableUnitsForMaterial($rawMaterialId)
    {
        if (!$rawMaterialId) {
            $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();
            return $lengthGroup ? $lengthGroup->activeUnits : \App\Models\Unit::where('is_active', true)->get();
        }
        $mat = \App\Models\RawMaterial::with('unitGroup.activeUnits', 'unitModel')->find($rawMaterialId);
        if ($mat && $mat->unitGroup && $mat->unitGroup->activeUnits->isNotEmpty()) {
            return $mat->unitGroup->activeUnits;
        }
        $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();
        return $lengthGroup ? $lengthGroup->activeUnits : \App\Models\Unit::where('is_active', true)->get();
    }

    public function getRollConvertedLengthInMeters(int $index): float
    {
        $item = $this->baleRollLengths[$index] ?? null;
        if (!$item) return 0.0;
        $lenVal = (float) (is_array($item) ? ($item['length'] ?? 0) : $item);
        if ($lenVal <= 0) return 0.0;

        $unitId = is_array($item) ? ($item['unit_id'] ?? null) : null;
        if ($unitId) {
            $unitModel = \App\Models\Unit::find($unitId);
            if ($unitModel) {
                return (float) $unitModel->toBaseQuantity($lenVal);
            }
        }

        return $lenVal;
    }

    public function triggerOpenBaleModal(int $baleId)
    {
        $bale = \App\Models\InventoryBale::with(['batch.rawMaterial', 'baleItems.rawMaterial'])->findOrFail($baleId);
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

        $bale = \App\Models\InventoryBale::with('batch.rawMaterial')->find($this->activeBaleIdToOpen);
        $defaultMatId = $bale?->availableMaterials->first()?->id ?? $bale?->batch?->raw_material_id;

        $widths = $this->getAvailableWidthsForMaterial($defaultMatId);
        $defaultWidthId = $widths->first()?->id ?? '';

        $units = $this->getAvailableUnitsForMaterial($defaultMatId);
        $matObj = $defaultMatId ? \App\Models\RawMaterial::find($defaultMatId) : null;
        $defaultUnitId = $matObj?->unit_id ?? $units->firstWhere('short_code', 'M')?->id ?? $units->first()?->id ?? '';

        $currentCount = count($this->baleRollLengths);
        if ($currentCount < $count) {
            for ($i = $currentCount; $i < $count; $i++) {
                $this->baleRollLengths[$i] = [
                    'length'          => '',
                    'unit_id'         => (string) $defaultUnitId,
                    'raw_material_id' => (string) $defaultMatId,
                    'fabric_width_id' => (string) $defaultWidthId,
                ];
            }
        } else if ($currentCount > $count) {
            $this->baleRollLengths = array_slice($this->baleRollLengths, 0, $count);
        }

        $this->checkBaleMismatchWarning();
    }

    public function updatedBaleRollLengths($value, $key)
    {
        if (str_contains($key, 'raw_material_id')) {
            $index = (int) explode('.', $key)[0];
            $matId = (int) $value;
            if ($matId) {
                $widths = $this->getAvailableWidthsForMaterial($matId);
                $this->baleRollLengths[$index]['fabric_width_id'] = (string) ($widths->first()?->id ?? '');

                $units = $this->getAvailableUnitsForMaterial($matId);
                $matObj = \App\Models\RawMaterial::find($matId);
                $defaultUnitId = $matObj?->unit_id ?? $units->firstWhere('short_code', 'M')?->id ?? $units->first()?->id ?? '';
                $this->baleRollLengths[$index]['unit_id'] = (string) $defaultUnitId;
            }
        }

        $this->checkBaleMismatchWarning();
    }

    protected function checkBaleMismatchWarning()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::find($this->activeBaleIdToOpen);
        if (!$bale) return;

        $filledIndices = array_filter(
            array_keys($this->baleRollLengths),
            fn($i) => isset($this->baleRollLengths[$i]) && (is_array($this->baleRollLengths[$i]) ? ($this->baleRollLengths[$i]['length'] ?? '') : $this->baleRollLengths[$i]) !== ''
        );

        if (empty($filledIndices)) {
            $this->baleMismatchWarning = null;
            return;
        }

        $sumBaseMeters = 0.0;
        foreach ($filledIndices as $i) {
            $sumBaseMeters += $this->getRollConvertedLengthInMeters($i);
        }

        $sumBaseMeters = round($sumBaseMeters, 2);
        $declared = (float) $bale->declared_length;

        if (abs($sumBaseMeters - $declared) > 0.001) {
            $diff = round($sumBaseMeters - $declared, 2);
            $sign = $diff > 0 ? "+{$diff}" : "{$diff}";
            $this->baleMismatchWarning = "Warning: Total measured roll length ({$sumBaseMeters}m) differs from declared purchase bale length ({$declared}m) by {$sign}m. This measured length ({$sumBaseMeters}m) will override the declared length for material calculations.";
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

        foreach ($this->baleRollLengths as $i => $item) {
            $len = is_array($item) ? ($item['length'] ?? '') : $item;
            if ($len === '' || $len === null || (float)$len <= 0) {
                $this->addError("baleRollLengths.{$i}.length", "Please enter a valid length for Roll #" . ($i + 1));
                return;
            }
        }

        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);
        $sumBaseMeters = 0.0;
        foreach (array_keys($this->baleRollLengths) as $i) {
            $sumBaseMeters += $this->getRollConvertedLengthInMeters($i);
        }
        $sumBaseMeters = round($sumBaseMeters, 2);
        $declared = (float) $bale->declared_length;

        if (abs($sumBaseMeters - $declared) > 0.001 && !$this->showMismatchConfirmationModal) {
            $this->showMismatchConfirmationModal = true;
            return;
        }

        $this->saveOpenedBale();
    }

    public function saveOpenedBale()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);

        $rollData = [];
        foreach ($this->baleRollLengths as $i => $item) {
            $lengthInBaseMeters = $this->getRollConvertedLengthInMeters($i);
            $rollData[] = [
                'length'          => (float) $lengthInBaseMeters,
                'raw_material_id' => !empty($item['raw_material_id']) ? (int) $item['raw_material_id'] : null,
                'fabric_width_id' => !empty($item['fabric_width_id']) ? (int) $item['fabric_width_id'] : null,
                'design_number'   => $bale->design_number ?? null,
                'stock_id'        => $bale->stock_id ?? null,
            ];
        }

        $result = $bale->openBale($rollData);
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
            'bales.baleItems.rawMaterial',
            'bales.rolls.rawMaterial',
        ]);

        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded for stock calculations.", type: 'success');
    }

    public function render()
    {
        return view('livewire.factory.inventory-batch-detail');
    }
}
?>
