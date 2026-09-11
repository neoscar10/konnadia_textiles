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
    <div class="bg-white rounded-2xl border border-gray-200/80 p-3 shadow-sm sticky top-0 z-10">
        <div class="grid grid-cols-3 gap-2 text-center text-xs font-extrabold">
            <button type="button" wire:click="setWizardStep(1)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 1 ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 1 ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} text-[11px] flex items-center justify-center">1</span>
                <span>Basic Information</span>
            </button>

            <button type="button" wire:click="setWizardStep(2)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 2 ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 2 ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} text-[11px] flex items-center justify-center">2</span>
                <span>Patterns & Task Routing</span>
            </button>

            <button type="button" wire:click="setWizardStep(3)" class="py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 {{ $wizardStep === 3 ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
                <span class="w-5 h-5 rounded-full {{ $wizardStep === 3 ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} text-[11px] flex items-center justify-center">3</span>
                <span>Subsidiary Materials</span>
            </button>
        </div>
    </div>

    <!-- Wizard Form Body -->
    <form wire:submit.prevent="save" class="space-y-6">

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs space-y-1.5 shadow-2xs">
                <div class="font-extrabold text-sm flex items-center gap-1.5 text-rose-900">
                    <span>⚠️</span>
                    <span>Please correct the following errors before saving:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-semibold text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- STEP 1: Basic Info -->
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
            </div>

            <!-- Navigation Bar Step 1 -->
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('factory.products.index') }}" wire:navigate class="px-6 py-2.5 bg-white border border-gray-200 hover:bg-slate-50 text-slate-800 font-bold text-sm rounded-full transition-all">
                    Cancel
                </a>
                <button type="button" wire:click="nextStep" class="px-7 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-sm rounded-full transition-all shadow-md flex items-center gap-2">
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
                        <p class="text-xs text-slate-500 mt-1">A pattern represents a size or cut variation of this product. Each pattern owns its fabric width consumption, task routing, and labor rate.</p>
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

                            <!-- 2-Column Side-by-Side Layout: Task Routing on Left, Pattern Specs on Right -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                <!-- LEFT SIDE: Task Routing Sequence -->
                                <div class="lg:col-span-7 p-4 bg-white border border-slate-200/90 rounded-2xl space-y-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                        <div>
                                            <span class="text-xs font-black uppercase tracking-wider text-slate-800">Task Sequence &amp; Labor Rate for {{ $pattern['name'] ?: 'Pattern' }}</span>
                                            <span class="text-[11px] font-medium text-slate-500 block mt-0.5">Configure task order, labor piece rates, and mark final stage.</span>
                                        </div>
                                    </div>

                                    <div class="space-y-2.5">
                                        @forelse($pattern['tasks'] ?? [] as $tIdx => $tRow)
                                            <div>
                                                <div class="inline-flex flex-wrap items-center gap-2 p-2 bg-slate-50/90 border border-slate-200/90 rounded-full transition-all hover:border-slate-300 shadow-2xs max-w-full">
                                                    <!-- Step Badge -->
                                                    <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-[11px] flex items-center justify-center shrink-0 shadow-2xs">
                                                        {{ $tIdx + 1 }}
                                                    </span>

                                                    <!-- Stage / Task Selection Dropdown -->
                                                    <div class="w-44 sm:w-48 shrink-0">
                                                        <select wire:model="patternsList.{{ $pIdx }}.tasks.{{ $tIdx }}.task_id" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-2xs">
                                                            <option value="">-- Select Stage --</option>
                                                            @foreach($availableTasks as $t)
                                                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <!-- Stage Labor Rate Input (₹) -->
                                                    <div class="w-20 sm:w-24 shrink-0">
                                                        <div class="relative flex items-center">
                                                            <span class="absolute left-2.5 text-xs font-extrabold text-slate-400">₹</span>
                                                            <input type="number" step="0.50" wire:model="patternsList.{{ $pIdx }}.tasks.{{ $tIdx }}.standard_labor_rate" placeholder="Rate" class="w-full pl-6 pr-2 py-1.5 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-2xs">
                                                        </div>
                                                    </div>

                                                    <!-- Final Stage Radio Pill -->
                                                    <label class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 rounded-full cursor-pointer text-xs font-bold text-slate-700 hover:bg-slate-100 transition-all shrink-0 select-none shadow-2xs">
                                                        <input type="radio" name="p_final_{{ $pIdx }}" wire:click="setPatternFinalStep({{ $pIdx }}, {{ $tIdx }})" @checked(!empty($tRow['is_final_step'])) class="text-amber-600 focus:ring-amber-500">
                                                        <span class="{{ !empty($tRow['is_final_step']) ? 'text-amber-700 font-extrabold' : 'text-slate-600' }}">Final</span>
                                                    </label>

                                                    <!-- Actions (Reorder & Delete) -->
                                                    <div class="flex items-center gap-1 shrink-0 pr-1">
                                                        @if($tIdx > 0)
                                                            <button type="button" wire:click="movePatternTaskRow({{ $pIdx }}, {{ $tIdx }}, 'up')" class="w-6 h-6 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-100 text-slate-600 text-xs font-bold transition-all" title="Move Up">↑</button>
                                                        @endif
                                                        @if($tIdx < count($pattern['tasks']) - 1)
                                                            <button type="button" wire:click="movePatternTaskRow({{ $pIdx }}, {{ $tIdx }}, 'down')" class="w-6 h-6 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-100 text-slate-600 text-xs font-bold transition-all" title="Move Down">↓</button>
                                                        @endif
                                                        <button type="button" wire:click="removePatternTaskRow({{ $pIdx }}, {{ $tIdx }})" class="w-6 h-6 rounded-full border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold transition-all" title="Remove Stage">✕</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-xs text-slate-400 italic p-3 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 w-fit">
                                                No tasks configured yet. Click "⚡ Load Category Task Sequence" above or add a task manually below.
                                            </div>
                                        @endforelse
                                    </div>

                                    <div class="pt-1">
                                        <button type="button" wire:click="addPatternTaskRow({{ $pIdx }})" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 font-extrabold text-xs rounded-xl transition-all shadow-2xs inline-flex items-center gap-1">
                                            <span>＋ Add Task</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- RIGHT SIDE: Pattern Fabric Width Consumption Card -->
                                <div class="lg:col-span-5 p-4 bg-white border border-slate-200/90 rounded-2xl space-y-4 shadow-2xs">
                                    <div class="border-b border-slate-100 pb-2.5">
                                        <span class="text-xs font-black uppercase tracking-wider text-slate-800">Fabric Width Consumption</span>
                                        <p class="text-[11px] font-medium text-slate-500 mt-1">Width is picked from the Fabric Width Master; enter the length consumed at that width in whichever length-based unit is convenient.</p>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($pattern['widths'] ?? [] as $wIdx => $wRow)
                                            <div class="flex items-center gap-2">
                                                <!-- Width Dropdown -->
                                                <div class="flex-1 min-w-0">
                                                    <select wire:model="patternsList.{{ $pIdx }}.widths.{{ $wIdx }}.fabric_width_id" class="w-full px-3 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                        <option value="">-- Select Width --</option>
                                                        @foreach($fabricWidths as $fw)
                                                            <option value="{{ $fw->id }}">{{ $fw->name }} ({{ $fw->value }} {{ $fw->unit }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Length Input -->
                                                <div class="w-24 shrink-0">
                                                    <input type="number" step="0.01" wire:model="patternsList.{{ $pIdx }}.widths.{{ $wIdx }}.fabric_length" placeholder="0.00" class="w-full px-3 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 text-right focus:outline-none focus:border-amber-500">
                                                </div>

                                                <!-- Length Unit Dropdown -->
                                                <div class="w-28 shrink-0">
                                                    <select wire:model="patternsList.{{ $pIdx }}.widths.{{ $wIdx }}.fabric_length_unit" class="w-full px-2 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                        <option value="m">Meter (m)</option>
                                                        <option value="in">Inch (in)</option>
                                                        <option value="cm">cm</option>
                                                        <option value="yd">Yard (yd)</option>
                                                    </select>
                                                </div>

                                                <!-- Remove Width Row Button -->
                                                @if(count($pattern['widths'] ?? []) > 1)
                                                    <button type="button" wire:click="removePatternWidthRow({{ $pIdx }}, {{ $wIdx }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition-all shrink-0" title="Remove width option">
                                                        ✕
                                                    </button>
                                                @endif
                                            </div>
                                            @error("patternsList.{$pIdx}.widths.{$wIdx}.fabric_width_id") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                            @error("patternsList.{$pIdx}.widths.{$wIdx}.fabric_length") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                        @endforeach

                                        <div>
                                            <button type="button" wire:click="addPatternWidthRow({{ $pIdx }})" class="text-xs font-extrabold text-amber-700 hover:text-amber-900 flex items-center gap-1 mt-1">
                                                <span>＋ Add width</span>
                                            </button>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Navigation Bar Step 2 -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="previousStep" class="px-6 py-2.5 bg-white border border-gray-200 hover:bg-slate-50 text-slate-800 font-bold text-sm rounded-full transition-all flex items-center gap-2">
                    <span>←</span>
                    <span>Back to Basic Info</span>
                </button>
                <button type="button" wire:click="nextStep" class="px-7 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-sm rounded-full transition-all shadow-md flex items-center gap-2">
                    <span>Next Step: Subsidiary Materials</span>
                    <span>→</span>
                </button>
            </div>
        @endif

        <!-- STEP 3: Subsidiary Materials & Final Save -->
        @if($wizardStep === 3)
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-extrabold text-slate-900 font-display uppercase tracking-wider">Step 3: Subsidiary Material Configuration</h3>
                    <p class="text-xs text-slate-500 mt-1">Choose whether every pattern in this product shares one subsidiary-material list, or whether patterns need different subsidiary materials from each other (e.g. a different zip colour per pattern) — either way, set it right here without needing the Patterns tab.</p>
                </div>

                <!-- Common List Switch Card -->
                <div class="p-5 bg-slate-50/80 border border-slate-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="font-extrabold text-sm text-slate-900 block">Common list for all patterns</span>
                        <p class="text-xs text-slate-500 mt-0.5">
                            @if($is_common_subsidiary)
                                All patterns share the single subsidiary-material list configured below.
                            @else
                                Each pattern below has its own subsidiary-material list — set them right here.
                            @endif
                        </p>
                    </div>

                    <!-- Explicit Dynamic Toggle Button -->
                    <button type="button" wire:click="toggleCommonSubsidiaryMode" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 {{ $is_common_subsidiary ? 'bg-amber-600' : 'bg-slate-300' }}" style="background-color: {{ $is_common_subsidiary ? '#d97706' : '#cbd5e1' }};" role="switch" aria-checked="{{ $is_common_subsidiary ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $is_common_subsidiary ? 'translate-x-5' : 'translate-x-0' }}" style="transform: {{ $is_common_subsidiary ? 'translateX(20px)' : 'translateX(0px)' }};"></span>
                    </button>
                </div>

                @if($is_common_subsidiary)
                    <!-- COMMON MODE: Single common list toggle & items -->
                    <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <span class="font-extrabold text-sm text-slate-900 block">Common Subsidiary Raw Material Use</span>
                                <p class="text-xs text-slate-500 mt-0.5">Enable if this manufacturing product consumes common subsidiary materials across all patterns.</p>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider transition-all {{ $is_subsidiary_used ? 'bg-amber-100 text-amber-800 border border-amber-300 shadow-2xs' : 'bg-slate-200 text-slate-600 border border-slate-300' }}">
                                    <span class="w-2 h-2 rounded-full {{ $is_subsidiary_used ? 'bg-amber-600 animate-pulse' : 'bg-slate-400' }}"></span>
                                    {{ $is_subsidiary_used ? 'Enabled' : 'Disabled' }}
                                </span>

                                <!-- Explicit Dynamic Toggle Button -->
                                <button type="button" wire:click="$toggle('is_subsidiary_used')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 {{ $is_subsidiary_used ? 'bg-amber-600' : 'bg-slate-300' }}" style="background-color: {{ $is_subsidiary_used ? '#d97706' : '#cbd5e1' }};" role="switch" aria-checked="{{ $is_subsidiary_used ? 'true' : 'false' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $is_subsidiary_used ? 'translate-x-5' : 'translate-x-0' }}" style="transform: {{ $is_subsidiary_used ? 'translateX(20px)' : 'translateX(0px)' }};"></span>
                                </button>
                            </div>
                        </div>

                        @if($is_subsidiary_used)
                            <div class="space-y-4 pt-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase text-slate-800">Common Subsidiary Items</span>
                                    <button type="button" wire:click="addSubsidiaryRow" class="px-3 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs rounded-lg transition-all">
                                        ＋ Add Material
                                    </button>
                                </div>

                                <div class="space-y-2.5">
                                    @foreach($subsidiaryMaterialsList as $sIdx => $sRow)
                                        <div>
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
                                            @error("subsidiaryMaterialsList.{$sIdx}.raw_material_id") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                            @error("subsidiaryMaterialsList.{$sIdx}.consumption_quantity") <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-slate-500 italic">Subsidiary use is off for all patterns.</p>
                        @endif
                    </div>
                @else
                    <!-- PER-PATTERN MODE: Individual pattern cards with individual toggles & lists -->
                    <div class="space-y-4">
                        @foreach($patternsList as $pIdx => $pattern)
                            <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-2xs">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center">
                                            {{ $pIdx + 1 }}
                                        </span>
                                        <span class="font-extrabold text-sm text-slate-900">{{ $pattern['name'] ?: 'Pattern ' . ($pIdx + 1) }}</span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-slate-500">
                                            {{ !empty($pattern['is_subsidiary_used']) ? 'Enabled' : 'Disabled' }}
                                        </span>
                                        <!-- Explicit Dynamic Toggle Button -->
                                        <button type="button" wire:click="togglePatternSubsidiary({{ $pIdx }})" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 {{ !empty($pattern['is_subsidiary_used']) ? 'bg-amber-600' : 'bg-slate-300' }}" style="background-color: {{ !empty($pattern['is_subsidiary_used']) ? '#d97706' : '#cbd5e1' }};" role="switch" aria-checked="{{ !empty($pattern['is_subsidiary_used']) ? 'true' : 'false' }}">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ !empty($pattern['is_subsidiary_used']) ? 'translate-x-5' : 'translate-x-0' }}" style="transform: {{ !empty($pattern['is_subsidiary_used']) ? 'translateX(20px)' : 'translateX(0px)' }};"></span>
                                        </button>
                                    </div>
                                </div>

                                @if(!empty($pattern['is_subsidiary_used']))
                                    <div class="space-y-3 pt-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-extrabold uppercase text-slate-700">Subsidiary Materials for {{ $pattern['name'] }}</span>
                                            <button type="button" wire:click="addPatternSubsidiaryRow({{ $pIdx }})" class="px-3 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs rounded-lg transition-all">
                                                ＋ Add Material
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            @forelse($pattern['subsidiaryMaterials'] ?? [] as $sIdx => $sRow)
                                                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                                    <div class="flex-1">
                                                        <select wire:model.live="patternsList.{{ $pIdx }}.subsidiaryMaterials.{{ $sIdx }}.raw_material_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
                                                            <option value="">-- Select Subsidiary Material --</option>
                                                            @foreach($subsidiaryRawMaterials as $m)
                                                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="w-36 flex items-center gap-2">
                                                        <input type="number" step="0.0001" wire:model="patternsList.{{ $pIdx }}.subsidiaryMaterials.{{ $sIdx }}.consumption_quantity" placeholder="Qty" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
                                                        <span class="text-xs font-bold text-slate-500 shrink-0">{{ $sRow['unit'] ?? '' }}</span>
                                                    </div>

                                                    <button type="button" wire:click="removePatternSubsidiaryRow({{ $pIdx }}, {{ $sIdx }})" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                                        ✕
                                                    </button>
                                                </div>
                                            @empty
                                                <p class="text-xs text-slate-400 italic">No materials added yet for this pattern. Click "＋ Add Material" above.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-500 italic">Subsidiary use is off for this pattern.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Navigation Bar Step 3 -->
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
