<div>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-[28px]" data-icon="percent">percent</span>
                Overhead Allocation Module
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                Track stitching & overhead material usage, fixed salaried staff costs, and monthly operational overheads relative to total production value.
            </p>
        </div>

        <!-- Month & Year Selector -->
        <div class="flex items-center gap-2 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant shadow-xs">
            <select wire:model.live="selectedMonth" class="font-label-md text-label-md font-bold bg-transparent text-on-surface border-none focus:ring-0 cursor-pointer py-1 pl-3 pr-8">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>
                @endforeach
            </select>
            <span class="text-outline-variant font-bold">/</span>
            <select wire:model.live="selectedYear" class="font-label-md text-label-md font-bold bg-transparent text-on-surface border-none focus:ring-0 cursor-pointer py-1 pl-2 pr-8">
                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Side: Detail Cards (7/12 on LG, 8/12 on XL) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            
            <!-- CARD 1: STITCHING & OVERHEAD MATERIAL - CLOSING STOCK -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 space-y-4">
                <div>
                    <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                        Stitching & General Overhead Material — Closing Stock, {{ $currentMonthName }}
                    </h3>
                </div>

                <div class="overflow-x-auto rounded-xl border border-outline-variant">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-5 py-3.5 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Material</th>
                                <th class="px-5 py-3.5 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Opening Stock (Qty)</th>
                                <th class="px-5 py-3.5 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Purchases This Month</th>
                                <th class="px-5 py-3.5 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Closing Stock (Qty) *</th>
                                <th class="px-5 py-3.5 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Consumed Qty & Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse($materialRows as $index => $row)
                                <tr class="hover:bg-surface-container transition-colors" wire:key="mat-row-{{ $row['raw_material_id'] }}">
                                    <td class="px-5 py-4">
                                        <div class="font-body-md text-body-md font-semibold text-on-surface">{{ $row['name'] }}</div>
                                        <div class="font-mono text-label-sm text-on-surface-variant">{{ $row['unit'] }} • {{ $row['code'] }} • ₹{{ number_format($row['unit_cost'], 2) }}/{{ $row['unit'] }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-body-sm text-on-surface-variant">
                                        <div class="font-bold text-on-surface">{{ number_format($row['opening_stock_qty'], 2) }} {{ $row['unit'] }}</div>
                                        <div class="text-xs text-slate-400">₹{{ number_format($row['opening_stock_value'], 2) }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-body-sm text-on-surface-variant">
                                        <div class="font-bold text-on-surface">{{ number_format($row['purchases_qty'], 2) }} {{ $row['unit'] }}</div>
                                        <div class="text-xs text-slate-400">₹{{ number_format($row['purchases_value'], 2) }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <input 
                                                type="number" 
                                                step="0.01"
                                                wire:model.live.debounce.300ms="materialRows.{{ $index }}.closing_stock_qty" 
                                                class="w-24 px-3 py-1.5 text-center font-mono font-bold font-body-sm text-body-sm bg-surface border border-outline-variant rounded-lg focus:ring-1 focus:ring-primary transition"
                                            />
                                            <span class="text-xs font-bold text-on-surface-variant">{{ $row['unit'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono">
                                        <div class="font-bold text-body-md text-on-surface">₹{{ number_format($row['consumed_cost'], 2) }}</div>
                                        <div class="text-xs text-emerald-600 font-semibold">{{ number_format($row['consumed_qty'], 2) }} {{ $row['unit'] }} used</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center font-body-md text-body-md text-on-surface-variant italic">
                                        No stitching or general overhead raw materials recorded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Stitching Total Footer -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-3 font-body-sm text-body-sm gap-2 border-t border-outline-variant">
                    <span class="font-body-md text-body-md text-on-surface-variant">
                        Total stitching & overhead material cost <span class="font-body-sm text-body-sm">(and its % of this month's production value)</span>
                    </span>
                    <div class="font-mono font-body-md text-body-md font-bold text-on-surface">
                        ₹{{ number_format($stitchingTotal, 2) }}
                        <span class="font-sans font-label-sm text-label-sm font-semibold text-on-surface-variant">
                            ({{ $productionValue > 0 ? number_format(($stitchingTotal / $productionValue) * 100, 1) : '0.0' }}% of production)
                        </span>
                    </div>
                </div>
            </div>

            <!-- CARD 2: SALARIED STAFF PAYMENT -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                        Salaried Staff Payment — From Labor Management
                    </h3>
                    <a href="{{ route('admin.labor.index') }}" wire:navigate class="font-label-md text-label-md font-bold text-primary hover:underline transition flex items-center gap-1">
                        Edit staff / salaries
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse($salariedLabors as $staff)
                        <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant">
                            <div>
                                <span class="font-body-md text-body-md font-semibold text-on-surface">{{ $staff->name }}</span>
                                <span class="text-on-surface-variant font-mono text-label-sm text-label-sm uppercase ml-2">
                                    {{ $staff->skill_level ?: $staff->labor_type ?: 'Salaried Staff' }}
                                </span>
                            </div>
                            <div class="font-mono font-body-md text-body-md font-bold text-on-surface">
                                ₹{{ number_format($staff->monthly_salary, 2) }}
                            </div>
                        </div>
                    @empty
                        <div class="p-5 rounded-xl border border-dashed border-outline-variant text-center font-body-md text-body-md text-on-surface-variant italic">
                            No salaried staff found in Labor Management with fixed monthly salary.
                        </div>
                    @endforelse
                </div>

                <!-- Salaried Staff Total Footer -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-3 font-body-sm text-body-sm gap-2 border-t border-outline-variant">
                    <span class="font-body-md text-body-md text-on-surface-variant">Total salaried staff payment</span>
                    <div class="font-mono font-body-md text-body-md font-bold text-on-surface">
                        ₹{{ number_format($salariedTotal, 2) }}
                        <span class="font-sans font-label-sm text-label-sm font-semibold text-on-surface-variant">
                            ({{ $productionValue > 0 ? number_format(($salariedTotal / $productionValue) * 100, 1) : '0.0' }}% of production)
                        </span>
                    </div>
                </div>
            </div>

            <!-- CARD 3: OTHER OVERHEADS -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 space-y-4">
                <div>
                    <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                        Other Overheads
                    </h3>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-12 gap-3 font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface-variant px-1">
                        <div class="col-span-7">Category</div>
                        <div class="col-span-4">Amount (₹)</div>
                        <div class="col-span-1 text-center"></div>
                    </div>

                    @foreach($otherOverheadRows as $oIndex => $oRow)
                        <div class="grid grid-cols-12 gap-3 items-start" wire:key="other-row-{{ $oIndex }}">
                            <div class="col-span-7 space-y-2">
                                <select 
                                    wire:model.live="otherOverheadRows.{{ $oIndex }}.category" 
                                    class="w-full bg-surface border border-outline-variant rounded-lg font-body-sm text-body-sm px-4 py-2.5 focus:ring-1 focus:ring-primary"
                                >
                                    @foreach($otherCategoryOptions as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @if(($oRow['category'] ?? '') === 'Other')
                                    <input 
                                        type="text" 
                                        wire:model.live="otherOverheadRows.{{ $oIndex }}.custom_category" 
                                        class="w-full bg-surface border border-outline-variant rounded-lg font-body-sm text-body-sm px-4 py-2 focus:ring-1 focus:ring-primary"
                                        placeholder="Type custom overhead category name..."
                                    />
                                @endif
                            </div>
                            <div class="col-span-4">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    wire:model.live="otherOverheadRows.{{ $oIndex }}.amount" 
                                    class="w-full bg-surface border border-outline-variant rounded-lg font-mono font-body-sm text-body-sm px-4 py-2.5 focus:ring-1 focus:ring-primary"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="col-span-1 text-center pt-1">
                                <button 
                                    type="button" 
                                    wire:click="removeOtherOverheadLine({{ $oIndex }})" 
                                    class="w-8 h-8 rounded-lg text-on-surface-variant hover:text-error hover:bg-error-container/20 inline-flex items-center justify-center transition"
                                    title="Remove row"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <button 
                        type="button" 
                        wire:click="addOtherOverheadLine" 
                        class="font-label-md text-label-md font-bold text-primary hover:underline inline-flex items-center gap-1 mt-2 transition"
                    >
                        + Add another line
                    </button>
                </div>

                <!-- Other Overheads Total Footer -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-3 font-body-sm text-body-sm gap-2 border-t border-outline-variant">
                    <span class="font-body-md text-body-md text-on-surface-variant">Total other overheads</span>
                    <div class="font-mono font-body-md text-body-md font-bold text-on-surface">
                        ₹{{ number_format($otherTotal, 2) }}
                        <span class="font-sans font-label-sm text-label-sm font-semibold text-on-surface-variant">
                            ({{ $productionValue > 0 ? number_format(($otherTotal / $productionValue) * 100, 1) : '0.0' }}% of production)
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side: Summary & History (5/12 on LG, 4/12 on XL) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-6">
            
            <!-- SUMMARY CARD -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 space-y-6">
                <div>
                    <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                        This Month's Summary — {{ $currentMonthName }}
                    </h3>
                </div>

                <!-- Production Value Input -->
                <div class="space-y-2 bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                    <label class="block font-label-md text-label-md font-bold text-on-surface uppercase tracking-wider">
                        Production Value for the Month (₹) *
                    </label>
                    <div>
                        <input 
                            type="number" 
                            step="0.01" 
                            wire:model.live.debounce.300ms="productionValue" 
                            class="w-full font-mono font-headline-sm text-headline-sm font-bold text-on-surface bg-surface border border-outline-variant rounded-lg px-4 py-2.5 focus:ring-1 focus:ring-primary"
                        />
                    </div>
                </div>

                <!-- Breakdown List -->
                <div class="space-y-3 font-body-md text-body-md">
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span>Stitching & overhead material</span>
                        <span class="font-mono font-bold text-on-surface">
                            ₹{{ number_format($stitchingTotal, 0) }} 
                            <span class="font-sans text-label-sm font-semibold text-on-surface-variant">({{ $productionValue > 0 ? number_format(($stitchingTotal / $productionValue) * 100, 1) : '0.0' }}%)</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span>Salaried staff payment</span>
                        <span class="font-mono font-bold text-on-surface">
                            ₹{{ number_format($salariedTotal, 0) }} 
                            <span class="font-sans text-label-sm font-semibold text-on-surface-variant">({{ $productionValue > 0 ? number_format(($salariedTotal / $productionValue) * 100, 1) : '0.0' }}%)</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span>Other overheads</span>
                        <span class="font-mono font-bold text-on-surface">
                            ₹{{ number_format($otherTotal, 0) }} 
                            <span class="font-sans text-label-sm font-semibold text-on-surface-variant">({{ $productionValue > 0 ? number_format(($otherTotal / $productionValue) * 100, 1) : '0.0' }}%)</span>
                        </span>
                    </div>
                </div>

                <div class="border-t border-outline-variant pt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-body-md text-body-md font-bold text-on-surface">Total overhead</span>
                        <span class="font-mono font-headline-sm text-headline-sm font-extrabold text-on-surface">
                            ₹{{ number_format($totalOverhead, 0) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between bg-amber-500/10 border border-amber-500/30 p-4 rounded-xl">
                        <span class="font-label-md text-label-md font-bold text-amber-900">Overhead % of production value</span>
                        <span class="font-mono font-headline-sm text-headline-sm font-extrabold text-amber-600">
                            {{ number_format($overheadPercentage, 1) }}%
                        </span>
                    </div>
                </div>

                <!-- Save Action Button -->
                <button 
                    type="button" 
                    wire:click="saveMonth" 
                    class="w-full py-3.5 px-6 bg-primary text-on-primary font-label-md text-label-md font-bold rounded-xl hover:shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[20px]" data-icon="save">save</span>
                    Save Month
                </button>
            </div>

            <!-- MONTH-ON-MONTH HISTORY CARD -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 space-y-4">
                <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                    Month-on-Month Overhead %
                </h3>

                <div class="overflow-x-auto rounded-xl border border-outline-variant">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-4 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Month</th>
                                <th class="px-4 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Total Overhead</th>
                                <th class="px-4 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Production Value</th>
                                <th class="px-4 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Overhead %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse($historyList as $hist)
                                <tr 
                                    wire:click="selectPeriod({{ $hist->year }}, {{ $hist->month }})" 
                                    class="hover:bg-surface-container cursor-pointer transition-colors {{ $hist->year == $selectedYear && $hist->month == $selectedMonth ? 'bg-primary-container/30 font-bold' : '' }}"
                                >
                                    <td class="px-4 py-3 font-body-md text-body-md font-semibold text-on-surface">
                                        {{ \Carbon\Carbon::createFromDate($hist->year, $hist->month, 1)->format('M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-body-sm text-on-surface-variant">
                                        ₹{{ number_format($hist->total_overhead, 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-body-sm text-on-surface-variant">
                                        ₹{{ number_format($hist->production_value, 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-body-md text-amber-600">
                                        {{ number_format($hist->overhead_percentage, 1) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center font-body-md text-body-md text-on-surface-variant italic">
                                        No saved historical periods yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
