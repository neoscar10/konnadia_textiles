<div class="space-y-6 max-w-5xl mx-auto pb-12">
    <!-- Back to Products Link -->
    <div>
        <a href="{{ route('factory.products.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 hover:text-amber-900 transition-colors">
            <span>←</span> Back to Manufacturing Products
        </a>
    </div>

    <!-- Page Title & Header -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-bold text-amber-700 tracking-wider uppercase mb-1">Manufacturing Product Wizard</div>
            <h1 class="text-2xl font-extrabold text-slate-900 font-display">
                {{ $productId ? 'Edit ' . $name : 'Add Manufacturing Product' }}
            </h1>
            <p class="text-slate-500 text-sm mt-1">
                Configure basic details, pattern variations, fabric dimensions, and task routing.
            </p>
        </div>
        @if($code)
            <div class="bg-slate-100 border border-slate-200 px-4 py-2 rounded-xl text-right shrink-0">
                <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Product Code</span>
                <span class="font-mono font-bold text-slate-900 text-base">{{ $code }}</span>
            </div>
        @endif
    </div>

    <!-- Wizard Stepper Navigation Header -->
    <div class="bg-white rounded-2xl border border-gray-200/80 p-2 shadow-sm">
        <div class="grid grid-cols-3 gap-2 text-center text-xs font-extrabold">
            <button type="button" wire:click="setWizardStep(1)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 1 ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 1 ? 'bg-amber-500 text-slate-950' : 'bg-slate-200 text-slate-700' }} text-[11px] flex items-center justify-center font-black">1</span>
                <span>Basic Info & Fabric</span>
            </button>

            <button type="button" wire:click="setWizardStep(2)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 2 ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 2 ? 'bg-amber-500 text-slate-950' : 'bg-slate-200 text-slate-700' }} text-[11px] flex items-center justify-center font-black">2</span>
                <span>Patterns</span>
                <span class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-600 border border-amber-500/40 uppercase ml-1">NEW</span>
            </button>

            <button type="button" wire:click="setWizardStep(3)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 3 ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 3 ? 'bg-amber-500 text-slate-950' : 'bg-slate-200 text-slate-700' }} text-[11px] flex items-center justify-center font-black">3</span>
                <span>Subsidiary Materials</span>
            </button>
        </div>
    </div>

    <!-- Info Notice Card -->
    <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-4 text-xs text-amber-900 flex items-start gap-3">
        <span class="text-amber-700 text-base leading-none mt-0.5">ⓘ</span>
        <div>
            <strong>Product &amp; Pattern Routing Architecture:</strong> Packaging is defined once per storefront front-end product during Finished Goods conversion. Patterns configured here dictate exact fabric widths, lengths, and task routing generated when Production Batches are created.
        </div>
    </div>

    <!-- Wizard Form Body -->
    <form wire:submit.prevent="save" class="space-y-6">

        <!-- STEP 1: Basic Info & Customised Product ID -->
        @if($wizardStep === 1)
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-extrabold text-slate-900 font-display uppercase tracking-wider">Step 1: Basic Information</h3>
                    <p class="text-xs text-slate-500 mt-1">Specify the manufacturing product's identification details and category assignment.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1.5">Product Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g., ktc dohar single" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1.5">Category *</label>
                        <select wire:model="manufacturing_product_category_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-amber-500 bg-white">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('manufacturing_product_category_id') <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Customised Product ID toggle (UI placeholder from prototype) -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-sm text-slate-900">Customised Product ID</span>
                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 uppercase">NEW</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">For one-off orders that don't follow a saved pattern specification.</p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" wire:model="is_customised_product" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                @if($is_customised_product)
                    <div class="grid grid-cols-2 gap-4 p-4 bg-white border border-slate-200 rounded-xl">
                        <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50">
                            <input type="radio" wire:model="customised_mode" value="fixed" class="mt-1 text-amber-600">
                            <div>
                                <strong class="block text-xs font-bold text-slate-900">Fixed length</strong>
                                <span class="text-[11px] text-slate-500">Same standard calculation as a saved pattern</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50">
                            <input type="radio" wire:model="customised_mode" value="calc" class="mt-1 text-amber-600">
                            <div>
                                <strong class="block text-xs font-bold text-slate-900">Calculate at job creation</strong>
                                <span class="text-[11px] text-slate-500">Length comes from actual fabric used in Cutting job</span>
                            </div>
                        </label>
                    </div>
                @endif
            </div>

            <!-- Navigation Bar Step 1 -->
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('factory.products.index') }}" wire:navigate class="px-6 py-2.5 bg-white border border-gray-200 hover:bg-slate-50 text-slate-800 font-bold text-sm rounded-full transition-all">
                    Cancel
                </a>
                <button type="button" wire:click="nextStep" class="px-7 py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-bold text-sm rounded-full transition-all shadow-sm flex items-center gap-2">
                    <span>Next Step: Patterns</span>
                    <span>→</span>
                </button>
            </div>
        @endif

        <!-- STEP 2: Patterns -->
        @if($wizardStep === 2)
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 font-display uppercase tracking-wider">Step 2: Patterns &amp; Task Routing</h3>
                        <p class="text-xs text-slate-500 mt-1">A pattern represents a size or cut variation of this product. Each pattern owns its fabric width, length, and task routing.</p>
                    </div>
                    <button type="button" wire:click="addPatternRow" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-full transition-all shadow-2xs">
                        ＋ Add Pattern
                    </button>
                </div>

                <div class="space-y-6">
                    @foreach($patternsList as $pIdx => $pattern)
                        <div class="p-5 bg-slate-50/80 border border-slate-200 rounded-2xl space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200/80 pb-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-full bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center">
                                        {{ $pIdx + 1 }}
                                    </span>
                                    <input type="text" wire:model="patternsList.{{ $pIdx }}.name" placeholder="Pattern Name (e.g. Standard Fold)" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-sm font-extrabold text-slate-900 focus:outline-none focus:border-amber-500">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="loadCategoryDefaultTasksForPattern({{ $pIdx }})" class="px-3 py-1 bg-white border border-amber-300 text-amber-800 hover:bg-amber-50 font-bold text-xs rounded-lg transition-all flex items-center gap-1 shadow-2xs">
                                        <span>⚡</span> Load Category Task Sequence
                                    </button>
                                    @if(count($patternsList) > 1)
                                        <button type="button" wire:click="removePatternRow({{ $pIdx }})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Remove Pattern">
                                            ✕
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Dimensions & Labor Rate -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Fabric Width Option *</label>
                                    <select wire:model="patternsList.{{ $pIdx }}.fabric_width_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                        <option value="">-- Select Fabric Width --</option>
                                        @foreach($fabricWidths as $fw)
                                            <option value="{{ $fw->id }}">{{ $fw->name }} ({{ $fw->value }} {{ $fw->unit }})</option>
                                        @endforeach
                                    </select>
                                    @error("patternsList.{$pIdx}.fabric_width_id") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Pattern Length *</label>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.01" wire:model="patternsList.{{ $pIdx }}.fabric_length" placeholder="Length" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                        <select wire:model="patternsList.{{ $pIdx }}.fabric_length_unit" class="px-2 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900">
                                            <option value="m">Meter (m)</option>
                                            <option value="in">Inch (in)</option>
                                            <option value="cm">cm</option>
                                            <option value="yd">Yard (yd)</option>
                                        </select>
                                    </div>
                                    @error("patternsList.{$pIdx}.fabric_length") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Standard Labor Rate (₹)</label>
                                    <input type="number" step="0.50" wire:model="patternsList.{{ $pIdx }}.standard_labor_rate" placeholder="Optional rate" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                </div>
                            </div>

                            <!-- Pattern Task Routing Repeater -->
                            <div class="p-3.5 bg-white border border-slate-200 rounded-xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase text-slate-800">Task Routing Sequence for {{ $pattern['name'] ?: 'Pattern' }}</span>
                                    <span class="text-[10px] font-bold text-slate-500">Click task row to reorder or edit labor rates</span>
                                </div>

                                <div class="space-y-2">
                                    @forelse($pattern['tasks'] ?? [] as $tIdx => $tRow)
                                        <div class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <span class="w-5 h-5 rounded-full bg-slate-800 text-white font-extrabold text-[10px] flex items-center justify-center shrink-0">
                                                {{ $tIdx + 1 }}
                                            </span>

                                            <div class="flex-1 min-w-[140px]">
                                                <select wire:model="patternsList.{{ $pIdx }}.tasks.{{ $tIdx }}.task_id" class="w-full px-2.5 py-1 bg-white border border-slate-200 rounded text-xs font-semibold text-slate-800">
                                                    <option value="">-- Select Task --</option>
                                                    @foreach($availableTasks as $t)
                                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="w-24">
                                                <input type="number" step="0.50" wire:model="patternsList.{{ $pIdx }}.tasks.{{ $tIdx }}.standard_labor_rate" placeholder="Rate (₹)" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs font-semibold">
                                            </div>

                                            <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-slate-700 shrink-0">
                                                <input type="radio" name="p_final_{{ $pIdx }}" wire:click="setPatternFinalStep({{ $pIdx }}, {{ $tIdx }})" @checked(!empty($tRow['is_final_step'])) class="text-amber-600">
                                                <span>Final</span>
                                            </label>

                                            <div class="flex items-center gap-1 shrink-0">
                                                @if($tIdx > 0)
                                                    <button type="button" wire:click="movePatternTaskRow({{ $pIdx }}, {{ $tIdx }}, 'up')" class="w-5 h-5 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-200 text-slate-600 text-[10px]">↑</button>
                                                @endif
                                                @if($tIdx < count($pattern['tasks']) - 1)
                                                    <button type="button" wire:click="movePatternTaskRow({{ $pIdx }}, {{ $tIdx }}, 'down')" class="w-5 h-5 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-200 text-slate-600 text-[10px]">↓</button>
                                                @endif
                                                <button type="button" wire:click="removePatternTaskRow({{ $pIdx }}, {{ $tIdx }})" class="w-5 h-5 rounded border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center text-[10px]">✕</button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-xs text-slate-400 italic p-2 text-center">
                                            No tasks configured yet. Click "⚡ Load Category Task Sequence" above or add a task manually below.
                                        </div>
                                    @endforelse
                                </div>

                                <button type="button" wire:click="addPatternTaskRow({{ $pIdx }})" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-lg transition-all">
                                    ＋ Add Task to Routing
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Navigation Bar Step 2 (Mandatory explicit Back & Next buttons) -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="previousStep" class="px-6 py-2.5 bg-white border border-gray-200 hover:bg-slate-50 text-slate-800 font-bold text-sm rounded-full transition-all flex items-center gap-2">
                    <span>←</span>
                    <span>Back to Basic Info</span>
                </button>
                <button type="button" wire:click="nextStep" class="px-7 py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-bold text-sm rounded-full transition-all shadow-sm flex items-center gap-2">
                    <span>Next Step: Subsidiary Materials</span>
                    <span>→</span>
                </button>
            </div>
        @endif

        <!-- STEP 3: Subsidiary Materials & Final Save -->
        @if($wizardStep === 3)
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-extrabold text-slate-900 font-display uppercase tracking-wider">Step 3: Subsidiary Materials Configuration</h3>
                    <p class="text-xs text-slate-500 mt-1">Add subsidiary materials (zippers, threads, tags) required for this product.</p>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-4">
                    <div>
                        <span class="font-extrabold text-sm text-slate-900">Configure Subsidiary Materials</span>
                        <p class="text-xs text-slate-500 mt-0.5">Enable if this manufacturing product consumes subsidiary raw materials.</p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" wire:model.live="is_subsidiary_used" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                @if($is_subsidiary_used)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase text-slate-800">Subsidiary Material Items</span>
                            <button type="button" wire:click="addSubsidiaryRow" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-lg transition-all">
                                ＋ Add Material
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach($subsidiaryMaterialsList as $sIdx => $sRow)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                    <div class="flex-1">
                                        <select wire:model.live="subsidiaryMaterialsList.{{ $sIdx }}.raw_material_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
                                            <option value="">-- Select Subsidiary Material --</option>
                                            @foreach($subsidiaryRawMaterials as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="w-36 flex items-center gap-2">
                                        <input type="number" step="0.0001" wire:model="subsidiaryMaterialsList.{{ $sIdx }}.consumption_quantity" placeholder="Qty" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
                                        <span class="text-xs font-bold text-slate-500 shrink-0">{{ $sRow['unit'] ?? '' }}</span>
                                    </div>

                                    <button type="button" wire:click="removeSubsidiaryRow({{ $sIdx }})" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                        ✕
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Navigation Bar Step 3 (Mandatory explicit Back & Save buttons) -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="previousStep" class="px-6 py-2.5 bg-white border border-gray-200 hover:bg-slate-50 text-slate-800 font-bold text-sm rounded-full transition-all flex items-center gap-2">
                    <span>←</span>
                    <span>Back to Patterns</span>
                </button>
                <button type="submit" class="px-8 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-sm rounded-full transition-all shadow-md flex items-center gap-2">
                    <span>✓</span>
                    <span>Save Manufacturing Product</span>
                </button>
            </div>
        @endif
    </form>
</div>
