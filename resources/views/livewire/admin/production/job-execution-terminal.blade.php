<div>
    <!-- Page Header Content -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg uppercase tracking-wider">Manufacturing Terminal</span>
                <span class="text-outline text-xs font-bold">• Step {{ $currentStep }} of 2</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Job Execution Terminal</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Guided 2-step workflow: Setup work order details, then allocate workers & record output.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.production.tracking-history') }}" wire:navigate class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant/60 hover:border-primary/40 px-4 py-2.5 rounded-xl font-label-md text-label-md text-primary font-bold shadow-xs hover:shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[20px]">history</span>
                Tracking History Log
            </a>
        </div>
    </div>

    <!-- Stepper Navigation Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-3 mb-6 shadow-xs">
        <div class="flex items-center justify-between gap-4 max-w-2xl mx-auto">
            <!-- Step 1 Tab -->
            <button type="button" wire:click="goToStep(1)" class="flex-1 flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl font-bold text-sm transition-all {{ $currentStep === 1 ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-high/50 text-on-surface-variant hover:bg-surface-container-high' }}">
                <span class="w-6 h-6 rounded-full {{ $currentStep === 1 ? 'bg-white text-primary' : ($currentStep > 1 ? 'bg-secondary text-white' : 'bg-outline-variant text-on-surface-variant') }} text-xs font-extrabold flex items-center justify-center shrink-0">
                    @if($currentStep > 1)
                        <span class="material-symbols-outlined text-[16px]">check</span>
                    @else
                        1
                    @endif
                </span>
                <span class="truncate">1. Work Order Setup</span>
            </button>

            <span class="material-symbols-outlined text-outline text-[18px]">chevron_right</span>

            <!-- Step 2 Tab -->
            <button type="button" wire:click="goToStep(2)" {{ !$manufacturing_product_id || !$task_id ? 'disabled' : '' }} class="flex-1 flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl font-bold text-sm transition-all {{ $currentStep === 2 ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-high/50 text-on-surface-variant hover:bg-surface-container-high opacity-70 cursor-not-allowed' }}">
                <span class="w-6 h-6 rounded-full {{ $currentStep === 2 ? 'bg-white text-primary' : 'bg-outline-variant text-on-surface-variant' }} text-xs font-extrabold flex items-center justify-center shrink-0">
                    2
                </span>
                <span class="truncate">2. Worker Allocation</span>
            </button>
        </div>
    </div>

    <!-- STEP 1: WORK ORDER SETUP PHASE -->
    @if($currentStep === 1)
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs mb-8">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/40">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">tune</span>
                </div>
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Step 1: Configure Work Order & Production Stage</h3>
                    <p class="text-body-md text-on-surface-variant">Select or enter the active job, product, stage, and total target output quantity.</p>
                </div>
            </div>

            <form wire:submit.prevent="proceedToAllocation" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                    <!-- Job ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Job ID / Work Order *</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px]">assignment</span>
                            <input type="text" wire:model.live="job_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl pl-10 pr-4 py-3 font-bold text-sm text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="e.g. JOB-2026-001">
                        </div>
                        @error('job_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Production Batch ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Production Batch ID</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px]">layers</span>
                            <input type="text" wire:model.live="production_batch_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl pl-10 pr-4 py-3 font-bold text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="e.g. PB-2026-00125">
                        </div>
                        @error('production_batch_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Manufacturing Product -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Manufacturing Product *</label>
                        <select wire:model.live="manufacturing_product_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 font-bold text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
                            <option value="">-- Choose Product --</option>
                            @foreach($allProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                            @endforeach
                        </select>
                        @error('manufacturing_product_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Stage / Task -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Production Stage / Task *</label>
                        <select wire:model.live="task_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 font-bold text-sm text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
                            <option value="">-- Choose Stage --</option>
                            @foreach($allTasks as $taskItem)
                                <option value="{{ $taskItem->id }}">{{ $taskItem->name }} ({{ $taskItem->code }})</option>
                            @endforeach
                        </select>
                        @error('task_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Total Job Quantity -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Total Job Target Qty (Units) *</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px]">inventory_2</span>
                            <input type="number" min="1" wire:model.live="total_quantity" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl pl-10 pr-16 py-3 font-black text-lg text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="100">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-outline uppercase">Units</span>
                        </div>
                        @error('total_quantity') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Action Bar Step 1 -->
                <div class="pt-6 border-t border-outline-variant/40 flex justify-end">
                    <button type="submit" class="bg-primary text-on-primary px-8 py-3.5 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center gap-3">
                        Proceed to Worker Allocation
                        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- STEP 2: LABORER ALLOCATION PHASE -->
    @if($currentStep === 2)
        <!-- Read-Only Active Work Order Summary Banner -->
        <div class="bg-primary text-on-primary rounded-2xl p-5 sm:p-6 mb-6 shadow-md relative overflow-hidden">
            <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 bg-white/20 text-white text-[11px] font-bold rounded-full uppercase tracking-wider">Active Work Order</span>
                        <span class="text-primary-fixed-dim text-xs font-mono font-bold">{{ $job_id }}</span>
                        @if($production_batch_id)
                            <span class="text-primary-fixed-dim/60 text-xs font-mono">• {{ $production_batch_id }}</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-black text-white tracking-tight">
                        {{ $currentProduct ? $currentProduct->name : 'Manufacturing Product' }}
                    </h3>
                    <p class="text-sm text-primary-fixed-dim flex items-center gap-2">
                        <span>Stage: <strong class="text-white font-bold">{{ $currentTask ? $currentTask->name : 'Task' }}</strong></span>
                        <span>•</span>
                        <span>Target: <strong class="text-white font-bold">{{ $total_quantity }} Units</strong></span>
                    </p>
                </div>

                <button type="button" wire:click="goToStep(1)" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    Change Setup Details
                </button>
            </div>
        </div>

        <!-- KPI Summary Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 truncate">Target Job Quantity</p>
                    <p class="text-2xl font-black text-primary">{{ $total_quantity }} <span class="text-xs text-outline font-normal">Units</span></p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">inventory</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 truncate">Allocated Quantity</p>
                    <p class="text-2xl font-black text-secondary truncate">
                        {{ $total_quantity - $this->remainingQuantity }} <span class="text-xs text-outline font-medium">/ {{ $total_quantity }}</span>
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-secondary/10 text-secondary font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">task_alt</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 truncate">Remaining to Allocate</p>
                    <p class="text-2xl font-black truncate {{ $this->remainingQuantity < 0 ? 'text-error' : ($this->remainingQuantity === 0 ? 'text-secondary' : 'text-primary') }}">
                        {{ $this->remainingQuantity }} <span class="text-xs text-outline font-medium">Units</span>
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $this->remainingQuantity < 0 ? 'bg-error/10 text-error' : ($this->remainingQuantity === 0 ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary') }} font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">
                        {{ $this->remainingQuantity < 0 ? 'warning' : ($this->remainingQuantity === 0 ? 'verified' : 'pending') }}
                    </span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 truncate">Authorized Laborers</p>
                    <p class="text-2xl font-black text-on-surface truncate">
                        {{ count($authorizedLabors) }} <span class="text-xs text-outline font-medium">Workers</span>
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-surface-container-high text-on-surface font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">badge</span>
                </div>
            </div>
        </div>

        <!-- Main Allocation Form Section -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-outline-variant/40">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">groups</span>
                        Step 2: Assign Laborers & Record Processed Output
                    </h3>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">
                        Add authorized workers for stage <strong class="text-primary">{{ $currentTask ? $currentTask->name : '' }}</strong> and allocate processed quantities.
                    </p>
                </div>
                
                <button type="button" wire:click="addLaborRow" class="flex items-center gap-2 bg-primary-fixed border border-primary/20 text-on-primary-fixed-variant px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-primary-fixed-dim transition-all font-bold shadow-xs active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Add Worker Row
                </button>
            </div>

            @if($errors->has('laborAllocations'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('laborAllocations') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="space-y-4">
                <div class="space-y-3">
                    @foreach($laborAllocations as $index => $allocation)
                        @php
                            $selectedLabor = $authorizedLabors->firstWhere('id', $allocation['labor_id']);
                        @endphp

                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-outline-variant/60 shadow-xs hover:border-primary/40 transition-all">
                            <!-- Index Badge + Worker Select -->
                            <div class="col-span-12 lg:col-span-6 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary text-on-primary font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Authorized Worker *</label>
                                    <select wire:model.live="laborAllocations.{{ $index }}.labor_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
                                        <option value="">-- Choose Worker --</option>
                                        @foreach($authorizedLabors as $labor)
                                            <option value="{{ $labor->id }}">
                                                {{ $labor->name }} ({{ $labor->code }}) — {{ $labor->payment_method === 'monthly_salary' ? 'Monthly Salaried' : 'Piece Rate' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("laborAllocations.{$index}.labor_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>

                            <!-- Laborer Type Badge -->
                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center">
                                @if($selectedLabor)
                                    <div class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 border w-full {{ $selectedLabor->payment_method === 'monthly_salary' ? 'bg-primary-fixed/40 text-on-primary-fixed-variant border-primary/20' : 'bg-secondary-container/40 text-on-secondary-container border-secondary/20' }}">
                                        <span class="material-symbols-outlined text-[20px] shrink-0">
                                            {{ $selectedLabor->payment_method === 'monthly_salary' ? 'account_balance_wallet' : 'payments' }}
                                        </span>
                                        <div class="truncate">
                                            <p class="font-bold text-[11px] uppercase tracking-wider">{{ $selectedLabor->payment_method === 'monthly_salary' ? 'Monthly Salaried' : 'Job Work (Piece Rate)' }}</p>
                                            <p class="text-[10px] opacity-80 font-medium">{{ $selectedLabor->payment_method === 'monthly_salary' ? 'Fixed Monthly Wage' : 'Calculated Per Unit Output' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="px-3.5 py-2 rounded-xl text-xs text-outline bg-surface-container-high/30 border border-outline-variant/30 italic w-full flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] opacity-50">info</span>
                                        Select worker to view wage type
                                    </div>
                                @endif
                            </div>

                            <!-- Quantity & Action -->
                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Processed Qty *</label>
                                    <div class="relative">
                                        <input type="number" min="1" wire:model.live="laborAllocations.{{ $index }}.quantity" placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-12 py-2.5 text-sm font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] text-outline font-bold uppercase">Pcs</span>
                                    </div>
                                    @error("laborAllocations.{$index}.quantity") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(count($laborAllocations) > 1)
                                    <button type="button" wire:click="removeLaborRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Allocation Live Summary Banner -->
                <div class="p-4 sm:p-5 rounded-2xl border flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 {{ $this->remainingQuantity === 0 ? 'bg-secondary-container/20 border-secondary' : ($this->remainingQuantity < 0 ? 'bg-error-container/30 border-error' : 'bg-surface-container-high/40 border-outline-variant/60') }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-2xl shrink-0 {{ $this->remainingQuantity === 0 ? 'text-secondary' : ($this->remainingQuantity < 0 ? 'text-error' : 'text-primary') }}">
                            {{ $this->remainingQuantity === 0 ? 'check_circle' : ($this->remainingQuantity < 0 ? 'warning' : 'info') }}
                        </span>
                        <div>
                            <p class="font-body-md font-bold text-on-surface">
                                @if($this->remainingQuantity === 0)
                                    Perfect! 100% of job quantity ({{ $total_quantity }} Units) allocated among workers.
                                @elseif($this->remainingQuantity < 0)
                                    Over-allocated by {{ abs($this->remainingQuantity) }} Units! Adjust worker quantities before submitting.
                                @else
                                    {{ $this->remainingQuantity }} Units remaining to be allocated among workers.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 w-full sm:w-auto">
                        <button type="submit" class="w-full sm:w-auto bg-primary text-on-primary px-8 py-3.5 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                            Complete Stage & Record Wages
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
