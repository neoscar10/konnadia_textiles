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

        // Fetch Raw Materials strictly of category CAT-SUB, CAT-STITCH, CAT-OHD or category name containing subsidiary/stitching
        $materials = RawMaterial::with('category')
            ->whereHas('category', function ($c) {
                $c->whereIn('code', ['CAT-SUB', 'CAT-STITCH', 'CAT-OHD'])
                  ->orWhere('name', 'like', '%subsidiary%')
                  ->orWhere('name', 'like', '%stitching%');
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
            $purchasesVal = (float) InventoryBatch::where('raw_material_id', $matId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->sum(fn($b) => (float)($b->total_amount ?: ($b->received_quantity * ($b->purchase_rate ?: $b->unit_cost ?: 10))));

            // Opening stock value
            if (isset($prevItemsMap[$matId])) {
                $openingVal = (float) $prevItemsMap[$matId]->closing_stock_value;
            } else {
                // Fallback to estimated initial stock value
                $unitCost = (float) ($mat->unit_cost ?: 100);
                $currentStock = (float) ($mat->stock_balance ?: $mat->opening_stock_quantity ?: 40);
                $openingVal = $currentStock * $unitCost * 0.7; // Estimate
            }

            if ($openingVal <= 0) {
                $openingVal = 1000.00;
            }

            if (isset($existingItemsMap[$matId])) {
                $eItem = $existingItemsMap[$matId];
                $closingQty = (float) $eItem->closing_stock_qty;
                $closingVal = (float) $eItem->closing_stock_value;
            } else {
                $closingQty = 20.00;
                $unitCost = (float) ($mat->unit_cost ?: 100);
                $closingVal = max(0, ($openingVal + $purchasesVal) * 0.5);
            }

            $consumedCost = max(0, $openingVal + $purchasesVal - $closingVal);

            $this->materialRows[] = [
                'raw_material_id' => $matId,
                'name' => $mat->name,
                'code' => $mat->code,
                'unit' => $mat->unit ?: 'Pcs',
                'opening_stock_value' => round($openingVal, 2),
                'purchases_value' => round($purchasesVal, 2),
                'closing_stock_qty' => round($closingQty, 2),
                'closing_stock_value' => round($closingVal, 2),
                'consumed_cost' => round($consumedCost, 2),
            ];
        }
    }

    public function updatedMaterialRows($value, $key)
    {
        // Re-calculate consumed cost when closing stock value or qty changes
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = intval($parts[0]);
            $field = $parts[1] ?? '';

            if (isset($this->materialRows[$index])) {
                $row = &$this->materialRows[$index];
                $openVal = floatval($row['opening_stock_value'] ?? 0);
                $purchVal = floatval($row['purchases_value'] ?? 0);
                $closeVal = floatval($row['closing_stock_value'] ?? 0);

                $row['consumed_cost'] = round(max(0, $openVal + $purchVal - $closeVal), 2);
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
                'opening_stock_value' => $mRow['opening_stock_value'],
                'purchases_value' => $mRow['purchases_value'],
                'closing_stock_qty' => $mRow['closing_stock_qty'],
                'closing_stock_value' => $mRow['closing_stock_value'],
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
