<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Factory\SaveOverheadAllocationRequest;
use App\Http\Resources\Api\V1\OverheadAllocationResource;
use App\Models\Labor;
use App\Models\MonthlyOverheadAllocation;
use App\Models\MonthlyOverheadMaterialItem;
use App\Models\MonthlyOverheadOtherItem;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverheadAllocationController extends Controller
{
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

    /**
     * Get standard category options.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'other_category_options' => $this->otherCategoryOptions,
            ],
        ]);
    }

    /**
     * Load calculation data or existing record for a given year & month.
     */
    public function show(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $data = $this->buildMonthData($year, $month);

        return response()->json([
            'success' => true,
            'data' => new OverheadAllocationResource($data),
        ]);
    }

    /**
     * Save / update allocation for year & month.
     */
    public function store(SaveOverheadAllocationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $prodValue = max(1.0, (float) $validated['production_value']);
        $periodDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        // Re-read or build base material rows to retain IDs and names
        $baseData = $this->buildMonthData($year, $month);
        $materialRows = $baseData['material_rows'];

        // Apply closing stock updates from payload if provided
        $inputMatMap = collect($validated['material_rows'] ?? [])->keyBy('raw_material_id');
        foreach ($materialRows as &$mRow) {
            $matId = $mRow['raw_material_id'];
            if (isset($inputMatMap[$matId])) {
                $closeQty = max(0.0, (float) $inputMatMap[$matId]['closing_stock_qty']);
                $openQty = (float) $mRow['opening_stock_qty'];
                $purchQty = (float) $mRow['purchases_qty'];
                $unitCost = (float) $mRow['unit_cost'];

                $consumedQty = max(0.0, $openQty + $purchQty - $closeQty);
                $mRow['closing_stock_qty'] = round($closeQty, 2);
                $mRow['closing_stock_value'] = round($closeQty * $unitCost, 2);
                $mRow['consumed_qty'] = round($consumedQty, 2);
                $mRow['consumed_cost'] = round($consumedQty * $unitCost, 2);
            }
        }
        unset($mRow);

        // Calculate totals
        $stitchingTotal = array_sum(array_column($materialRows, 'consumed_cost'));

        $salariedLabors = Labor::active()->where(function($q) {
            $q->where('payment_method', 'salary')->orWhere('monthly_salary', '>', 0);
        })->get();
        $salariedTotal = (float) $salariedLabors->sum('monthly_salary');

        $otherRows = [];
        $otherTotal = 0.0;
        foreach ($validated['other_overhead_rows'] ?? [] as $oRow) {
            $cat = $oRow['category'] ?? 'General Consumables';
            $customCat = trim($oRow['custom_category'] ?? '');
            $amt = max(0.0, (float) ($oRow['amount'] ?? 0));
            $catName = ($cat === 'Other') ? ($customCat ?: 'Other Overhead') : $cat;

            if ($catName !== '' || $amt > 0) {
                $otherRows[] = [
                    'category' => $cat,
                    'custom_category' => $customCat,
                    'category_name' => $catName,
                    'amount' => round($amt, 2),
                ];
                $otherTotal += $amt;
            }
        }

        $totalOverhead = $stitchingTotal + $salariedTotal + $otherTotal;
        $overheadPercentage = $prodValue > 0 ? round(($totalOverhead / $prodValue) * 100, 2) : 0.00;

        $allocation = MonthlyOverheadAllocation::updateOrCreate(
            [
                'year' => $year,
                'month' => $month,
            ],
            [
                'period_date' => $periodDate,
                'production_value' => $prodValue,
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
        foreach ($materialRows as $mRow) {
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

        // Sync other items
        $allocation->otherItems()->delete();
        foreach ($otherRows as $oRow) {
            MonthlyOverheadOtherItem::create([
                'monthly_overhead_allocation_id' => $allocation->id,
                'category_name' => $oRow['category_name'],
                'amount' => $oRow['amount'],
            ]);
        }

        $savedData = $this->buildMonthData($year, $month);

        return response()->json([
            'success' => true,
            'message' => 'Overhead allocation saved successfully.',
            'data' => new OverheadAllocationResource($savedData),
        ]);
    }

    /**
     * History list of saved overhead allocations (up to 12 months).
     */
    public function history(): JsonResponse
    {
        $history = MonthlyOverheadAllocation::orderBy('period_date', 'desc')
            ->limit(12)
            ->get()
            ->map(function ($alloc) {
                return [
                    'id' => $alloc->id,
                    'year' => $alloc->year,
                    'month' => $alloc->month,
                    'period_formatted' => $alloc->period_formatted,
                    'production_value' => (float) $alloc->production_value,
                    'stitching_material_total' => (float) $alloc->stitching_material_total,
                    'salaried_staff_total' => (float) $alloc->salaried_staff_total,
                    'other_overheads_total' => (float) $alloc->other_overheads_total,
                    'total_overhead' => (float) $alloc->total_overhead,
                    'overhead_percentage' => (float) $alloc->overhead_percentage,
                    'status' => $alloc->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Helper to compute/fetch monthly data structure matching web Livewire page logic.
     */
    protected function buildMonthData(int $year, int $month): array
    {
        $periodDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $existing = MonthlyOverheadAllocation::with(['materialItems.rawMaterial', 'otherItems'])
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        // Calculate auto production value if available
        $autoProdValue = (float) \App\Models\FinishedGoodsBatch::whereBetween('converted_date', [$startDate, $endDate])
            ->get()
            ->sum(fn($b) => (float)($b->converted_qty * 500));

        if ($existing) {
            $prodValue = (float) $existing->production_value;
            $otherRows = [];
            foreach ($existing->otherItems as $oItem) {
                $cat = $oItem->category_name;
                $isStandard = in_array($cat, array_diff($this->otherCategoryOptions, ['Other']));
                $otherRows[] = [
                    'category' => $isStandard ? $cat : 'Other',
                    'custom_category' => $isStandard ? '' : $cat,
                    'category_name' => $cat,
                    'amount' => (float) $oItem->amount,
                ];
            }
        } else {
            $prodValue = $autoProdValue > 0 ? $autoProdValue : 544200.00;
            $otherRows = [
                [
                    'category' => 'General Consumables',
                    'custom_category' => '',
                    'category_name' => 'General Consumables',
                    'amount' => 2400.00,
                ],
            ];
        }

        // Fetch Raw Materials of category CAT-STITCH, CAT-OHD
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
        $prevDate = Carbon::create($year, $month, 1)->subMonth();
        $prevAllocation = MonthlyOverheadAllocation::with('materialItems')
            ->where('year', (int)$prevDate->format('Y'))
            ->where('month', (int)$prevDate->format('m'))
            ->first();

        $prevItemsMap = $prevAllocation ? $prevAllocation->materialItems->keyBy('raw_material_id') : collect();
        $existingItemsMap = $existing ? $existing->materialItems->keyBy('raw_material_id') : collect();

        $materialRows = [];
        foreach ($materials as $mat) {
            $matId = $mat->id;

            $batches = InventoryBatch::where('raw_material_id', $matId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $purchasesQty = (float) $batches->sum('quantity_received');
            $purchasesVal = (float) $batches->sum(fn($b) => (float)($b->total_amount ?: ($b->received_quantity * ($b->purchase_rate ?: $b->unit_cost ?: 10))));

            if ($purchasesQty > 0 && $purchasesVal > 0) {
                $unitCost = round($purchasesVal / $purchasesQty, 2);
            } else {
                $unitCost = (float) ($mat->unit_cost ?: ($mat->purchase_rate ?: 100));
            }
            if ($unitCost <= 0) $unitCost = 100.00;

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

            $materialRows[] = [
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

        // Salaried Labors
        $salariedLabors = Labor::active()->where(function($q) {
            $q->where('payment_method', 'salary')->orWhere('monthly_salary', '>', 0);
        })->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'name' => $l->name,
                'role' => $l->role,
                'monthly_salary' => (float) $l->monthly_salary,
            ];
        })->values()->toArray();

        $stitchingTotal = array_sum(array_column($materialRows, 'consumed_cost'));
        $salariedTotal = array_sum(array_column($salariedLabors, 'monthly_salary'));
        $otherTotal = array_sum(array_column($otherRows, 'amount'));
        $totalOverhead = $stitchingTotal + $salariedTotal + $otherTotal;
        $overheadPercentage = $prodValue > 0 ? round(($totalOverhead / $prodValue) * 100, 2) : 0.00;

        return [
            'id' => $existing?->id,
            'year' => $year,
            'month' => $month,
            'period_formatted' => Carbon::createFromDate($year, $month, 1)->format('M Y'),
            'production_value' => round($prodValue, 2),
            'stitching_material_total' => round($stitchingTotal, 2),
            'salaried_staff_total' => round($salariedTotal, 2),
            'other_overheads_total' => round($otherTotal, 2),
            'total_overhead' => round($totalOverhead, 2),
            'overhead_percentage' => round($overheadPercentage, 2),
            'status' => $existing ? $existing->status : 'draft',
            'material_rows' => $materialRows,
            'salaried_labors' => $salariedLabors,
            'other_overhead_rows' => $otherRows,
        ];
    }
}
