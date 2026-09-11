<?php

namespace App\Livewire\Factory;

use App\Models\Labor;
use App\Models\MonthlyOverheadAllocation;
use App\Models\MonthlyOverheadMaterialItem;
use App\Models\MonthlyOverheadOtherItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout')]
class OverheadAllocationPage extends Component
{
    public int $selectedYear;
    public int $selectedMonth;

    // Form inputs
    public float $productionValue = 544200.00;
    public array $materialRows = [];
    public array $otherOverheadRows = [];

    // Category options for other overheads
    public array $otherCategoryOptions = [
        'General Consumables',
        'Electricity & Utilities',
        'Factory Rent Share',
        'Machine Maintenance & Repairs',
        'Water & Waste Handling',
        'Freight & Logistics Share',
        'Miscellaneous Expenses',
        'Other',
    ];

    public function mount()
    {
        $this->selectedYear = (int) now()->format('Y');
        $this->selectedMonth = (int) now()->format('m');

        $this->loadMonthData();
    }

    public function updatedSelectedYear()
    {
        $this->loadMonthData();
    }

    public function updatedSelectedMonth()
    {
        $this->loadMonthData();
    }

    public function loadMonthData()
    {
        $periodDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('Y-m-d');
        
        $existing = MonthlyOverheadAllocation::with(['materialItems.rawMaterial', 'otherItems'])
            ->where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->first();

        // Calculate auto production value if available
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        $autoProdValue = (float) \App\Models\FinishedGoodsBatch::whereBetween('converted_date', [$startDate, $endDate])
            ->get()
            ->sum(fn($b) => (float)($b->converted_qty * 500)); // Default valuation estimate

        if ($existing) {
            $this->productionValue = (float) $existing->production_value;
            $this->otherOverheadRows = [];
            foreach ($existing->otherItems as $oItem) {
                $cat = $oItem->category_name;
                $isStandard = in_array($cat, array_diff($this->otherCategoryOptions, ['Other']));
                $this->otherOverheadRows[] = [
                    'category' => $isStandard ? $cat : 'Other',
                    'custom_category' => $isStandard ? '' : $cat,
                    'amount' => (float) $oItem->amount,
                ];
            }
        } else {
            $this->productionValue = $autoProdValue > 0 ? $autoProdValue : 544200.00;
            $this->otherOverheadRows = [
                ['category' => 'General Consumables', 'custom_category' => '', 'amount' => 2400.00],
            ];
        }

        // Fetch Raw Materials strictly of category CAT-STITCH, CAT-OHD (stitching & general overheads)
        // Strictly exclude CAT-SUB and subsidiary materials
        $materials = RawMaterial::with('category')
            ->whereHas('category', function ($c) {
                $c->whereIn('code', ['CAT-STITCH', 'CAT-OHD'])
                  ->orWhere(function ($subQ) {
                      $subQ->where('name', 'like', '%stitching%')
                           ->orWhere('name', 'like', '%overhead%')
                           ->orWhere('name', 'like', '%consumable%');
                  });
            })
            ->whereHas('category', function ($c) {
                $c->where('code', '!=', 'CAT-SUB')
                  ->where('name', 'not like', '%subsidiary%');
            })
            ->where('is_active', true)
            ->get();

        // Previous month allocation for opening stock reference
        $prevDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $prevAllocation = MonthlyOverheadAllocation::with('materialItems')
            ->where('year', (int)$prevDate->format('Y'))
            ->where('month', (int)$prevDate->format('m'))
            ->first();

        $prevItemsMap = $prevAllocation ? $prevAllocation->materialItems->keyBy('raw_material_id') : collect();
        $existingItemsMap = $existing ? $existing->materialItems->keyBy('raw_material_id') : collect();

        $this->materialRows = [];
        foreach ($materials as $mat) {
            $matId = $mat->id;
            
            // Purchases in selected month
            $batches = InventoryBatch::where('raw_material_id', $matId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $purchasesQty = (float) $batches->sum('quantity_received');
            $purchasesVal = (float) $batches->sum(fn($b) => (float)($b->total_amount ?: ($b->received_quantity * ($b->purchase_rate ?: $b->unit_cost ?: 10))));

            // Determine effective unit cost for cost derivation
            if ($purchasesQty > 0 && $purchasesVal > 0) {
                $unitCost = round($purchasesVal / $purchasesQty, 2);
            } else {
                $unitCost = (float) ($mat->unit_cost ?: ($mat->purchase_rate ?: 100));
            }
            if ($unitCost <= 0) $unitCost = 100.00;

            // Opening stock quantity (stock at start of month)
            if (isset($prevItemsMap[$matId])) {
                $openingQty = (float) ($prevItemsMap[$matId]->closing_stock_qty ?? 0);
                if ((float)($prevItemsMap[$matId]->unit_cost ?? 0) > 0) {
                    $unitCost = (float) $prevItemsMap[$matId]->unit_cost;
                }
            } else {
                $openingQty = (float) ($mat->opening_stock_quantity ?: 0.00);
            }

            if (isset($existingItemsMap[$matId])) {
                $eItem = $existingItemsMap[$matId];
                $closingQty = (float) $eItem->closing_stock_qty;
                if ((float)($eItem->unit_cost ?? 0) > 0) {
                    $unitCost = (float) $eItem->unit_cost;
                }
            } else {
                $closingQty = max(0.0, $openingQty + $purchasesQty - 1.0);
            }

            $consumedQty = max(0.0, $openingQty + $purchasesQty - $closingQty);
            $openingVal = round($openingQty * $unitCost, 2);
            $closingVal = round($closingQty * $unitCost, 2);
            $consumedCost = round($consumedQty * $unitCost, 2);

            $this->materialRows[] = [
                'raw_material_id' => $matId,
                'name' => $mat->name,
                'code' => $mat->code,
                'unit' => $mat->unit ?: 'Pcs',
                'unit_cost' => round($unitCost, 2),
                'opening_stock_qty' => round($openingQty, 2),
                'opening_stock_value' => round($openingVal, 2),
                'purchases_qty' => round($purchasesQty, 2),
                'purchases_value' => round($purchasesVal, 2),
                'closing_stock_qty' => round($closingQty, 2),
                'closing_stock_value' => round($closingVal, 2),
                'consumed_qty' => round($consumedQty, 2),
                'consumed_cost' => round($consumedCost, 2),
            ];
        }
    }

    public function updatedMaterialRows($value, $key)
    {
        // Re-calculate consumed quantity and cost when closing stock quantity changes
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = intval($parts[0]);

            if (isset($this->materialRows[$index])) {
                $row = &$this->materialRows[$index];
                $openQty = floatval($row['opening_stock_qty'] ?? 0);
                $purchQty = floatval($row['purchases_qty'] ?? 0);
                $closeQty = floatval($row['closing_stock_qty'] ?? 0);
                $unitCost = floatval($row['unit_cost'] ?? 0);

                $consumedQty = max(0.0, $openQty + $purchQty - $closeQty);
                $row['consumed_qty'] = round($consumedQty, 2);
                $row['closing_stock_value'] = round($closeQty * $unitCost, 2);
                $row['consumed_cost'] = round($consumedQty * $unitCost, 2);
            }
        }
    }

    public function addOtherOverheadLine()
    {
        $this->otherOverheadRows[] = [
            'category' => 'Miscellaneous Expenses',
            'custom_category' => '',
            'amount' => 0.00,
        ];
    }

    public function removeOtherOverheadLine(int $index)
    {
        if (isset($this->otherOverheadRows[$index])) {
            unset($this->otherOverheadRows[$index]);
            $this->otherOverheadRows = array_values($this->otherOverheadRows);
        }
    }

    public function selectPeriod(int $year, int $month)
    {
        $this->selectedYear = $year;
        $this->selectedMonth = $month;
        $this->loadMonthData();
    }

    public function saveMonth()
    {
        $this->productionValue = max(1, floatval($this->productionValue));
        $periodDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('Y-m-d');

        // Calculate totals
        $stitchingTotal = array_sum(array_column($this->materialRows, 'consumed_cost'));
        
        $salariedLabors = Labor::active()->where(function($q) {
            $q->where('payment_method', 'salary')->orWhere('monthly_salary', '>', 0);
        })->get();
        $salariedTotal = (float) $salariedLabors->sum('monthly_salary');

        $otherTotal = array_sum(array_map('floatval', array_column($this->otherOverheadRows, 'amount')));

        $totalOverhead = $stitchingTotal + $salariedTotal + $otherTotal;
        $overheadPercentage = $this->productionValue > 0 ? round(($totalOverhead / $this->productionValue) * 100, 2) : 0.00;

        $allocation = MonthlyOverheadAllocation::updateOrCreate(
            [
                'year' => $this->selectedYear,
                'month' => $this->selectedMonth,
            ],
            [
                'period_date' => $periodDate,
                'production_value' => $this->productionValue,
                'stitching_material_total' => $stitchingTotal,
                'salaried_staff_total' => $salariedTotal,
                'other_overheads_total' => $otherTotal,
                'total_overhead' => $totalOverhead,
                'overhead_percentage' => $overheadPercentage,
                'status' => 'saved',
                'created_by' => auth()->id(),
            ]
        );

        // Sync material items
        $allocation->materialItems()->delete();
        foreach ($this->materialRows as $mRow) {
            MonthlyOverheadMaterialItem::create([
                'monthly_overhead_allocation_id' => $allocation->id,
                'raw_material_id' => $mRow['raw_material_id'],
                'opening_stock_qty' => $mRow['opening_stock_qty'],
                'opening_stock_value' => $mRow['opening_stock_value'],
                'purchases_qty' => $mRow['purchases_qty'],
                'purchases_value' => $mRow['purchases_value'],
                'unit_cost' => $mRow['unit_cost'],
                'closing_stock_qty' => $mRow['closing_stock_qty'],
                'closing_stock_value' => $mRow['closing_stock_value'],
                'consumed_qty' => $mRow['consumed_qty'],
                'consumed_cost' => $mRow['consumed_cost'],
            ]);
        }

        // Sync other overhead items
        $allocation->otherItems()->delete();
        foreach ($this->otherOverheadRows as $oRow) {
            $catName = ($oRow['category'] ?? '') === 'Other'
                ? (trim($oRow['custom_category'] ?? '') ?: 'Other Overhead')
                : ($oRow['category'] ?? 'Other Overhead');

            if (empty($catName) && empty($oRow['amount'])) {
                continue;
            }
            MonthlyOverheadOtherItem::create([
                'monthly_overhead_allocation_id' => $allocation->id,
                'category_name' => $catName,
                'amount' => floatval($oRow['amount'] ?? 0),
            ]);
        }

        session()->flash('toast', [
            'type' => 'success',
            'message' => "Overhead Allocation for " . Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('F Y') . " saved successfully!",
        ]);
    }

    public function render()
    {
        $salariedLabors = Labor::active()->where(function($q) {
            $q->where('payment_method', 'salary')->orWhere('monthly_salary', '>', 0);
        })->get();

        $stitchingTotal = array_sum(array_column($this->materialRows, 'consumed_cost'));
        $salariedTotal = (float) $salariedLabors->sum('monthly_salary');
        $otherTotal = array_sum(array_map('floatval', array_column($this->otherOverheadRows, 'amount')));
        $totalOverhead = $stitchingTotal + $salariedTotal + $otherTotal;
        $overheadPercentage = $this->productionValue > 0 ? round(($totalOverhead / $this->productionValue) * 100, 2) : 0.00;

        // Month-on-month history
        $historyList = MonthlyOverheadAllocation::orderBy('period_date', 'desc')->limit(12)->get();

        $currentMonthName = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('M Y');

        return view('livewire.factory.overhead-allocation-page', [
            'salariedLabors' => $salariedLabors,
            'stitchingTotal' => $stitchingTotal,
            'salariedTotal' => $salariedTotal,
            'otherTotal' => $otherTotal,
            'totalOverhead' => $totalOverhead,
            'overheadPercentage' => $overheadPercentage,
            'historyList' => $historyList,
            'currentMonthName' => strtoupper($currentMonthName),
        ])->title('Overhead Allocation Module');
    }
}
