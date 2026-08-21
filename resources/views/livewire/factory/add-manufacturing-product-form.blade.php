<div>
    <!-- Navigation / Breadcrumbs -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-on-surface-variant font-label-md mb-2 text-xs">
            <a class="hover:text-primary" href="{{ route('admin.dashboard') }}" wire:navigate>Dashboard</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <a class="hover:text-primary" href="{{ route('factory.products.index') }}" wire:navigate>Products</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-on-surface font-bold">{{ $productId ? 'Edit Product' : 'Add Product' }}</span>
        </nav>
        <h1 class="font-headline-lg text-headline-lg text-primary font-black">{{ $productId ? 'Edit Manufacturing Product' : 'Add Manufacturing Product' }}</h1>
        <p class="font-body-md text-on-surface-variant mt-1 text-sm">Configure basic information, materials, and routing sequence for manufacturing operations.</p>
    </div>

    <!-- Main Form Area -->
    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-6 items-start">
        <!-- Left/Center Content -->
        <div class="col-span-12 space-y-6">

            <!-- ═══════════════ WIZARD TABS ═══════════════ -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden mb-6 flex overflow-x-auto">
                <button type="button" wire:click="setWizardStep(1)" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $wizardStep === 1 ? 'text-primary border-b-2 border-primary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
                    1. Basic Info & Fabric
                </button>
                <button type="button" wire:click="setWizardStep(2)" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $wizardStep === 2 ? 'text-secondary border-b-2 border-secondary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-secondary transition-colors' }}">
                    2. Subsidiary BOM
                </button>
                <button type="button" wire:click="setWizardStep(3)" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $wizardStep === 3 ? 'text-tertiary border-b-2 border-tertiary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-tertiary transition-colors' }}">
                    3. Stitching
                </button>
                <button type="button" wire:click="setWizardStep(4)" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $wizardStep === 4 ? 'text-primary border-b-2 border-primary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
                    4. Task Routing
                </button>
            </div>

            @if($wizardStep === 1)
            <!-- ═══════════════ CARD 1: BASIC INFORMATION ═══════════════ -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Row 1: Product Name & Internal Code -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Product Name -->
                        <div>
                            <label class="block font-label-md text-on-surface-variant text-xs font-bold uppercase tracking-wider mb-2">Product Name *</label>
                            <input wire:model.blur="name" class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-4 py-3 bg-surface text-sm" type="text" placeholder="e.g. King Size Bedsheet"/>
                            @error('name') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <!-- Internal Code (Read-only) -->
                        <div>
                            <label class="block font-label-md text-on-surface-variant text-xs font-bold uppercase tracking-wider mb-2">Internal Code</label>
                            <input class="w-full rounded-xl border border-outline-variant/60 bg-surface-container-high/60 font-body-md px-4 py-3 text-on-surface-variant font-mono font-bold text-sm" readonly type="text" value="{{ $code }}"/>
                        </div>
                    </div>

                    <!-- Row 2: Category -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Category Selection -->
                        <div>
                            <label class="block font-label-md text-on-surface-variant text-xs font-bold uppercase tracking-wider mb-2">Category *</label>
                            <select wire:model.blur="manufacturing_product_category_id" class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-4 py-3 bg-surface font-bold text-sm">
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('manufacturing_product_category_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 3: Is Fabric Used? Toggle -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Is Fabric Used? Toggle -->
                        <div class="flex items-center justify-between p-4 bg-surface-container-low/50 rounded-xl border border-outline-variant/30">
                            <div>
                                <p class="font-body-md font-bold text-sm">Is Fabric Used?</p>
                                <p class="font-label-sm text-on-surface-variant text-xs">Enable if fabric mapping is required.</p>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model.live="is_fabric_used" class="w-6 h-6 rounded border-outline-variant text-primary focus:ring-primary"/>
                            </div>
                        </div>
                    </div>

                    @if($is_fabric_used)
                        <!-- Fabric Dimensions Bento Card -->
                        <div class="bg-surface-container-low/30 p-5 rounded-2xl border border-outline-variant/60 space-y-4">
                            <h4 class="font-headline-sm text-headline-sm font-bold text-sm text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined">straighten</span>
                                Standard Fabric Dimensions
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Width & Unit -->
                                <div>
                                    <label class="block font-label-md text-on-surface-variant text-xs font-bold uppercase tracking-wider mb-2">Standard Width *</label>
                                    <div class="flex gap-2 min-w-0">
                                        <input wire:model.blur="standard_fabric_width" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-3.5 py-2.5 bg-surface text-sm font-semibold" type="number" step="0.01" placeholder="60.00"/>
                                        <select wire:model.live="fabric_width_group_id" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 font-body-md px-2 py-2.5 bg-surface font-bold text-xs truncate">
                                            @foreach($allUnitGroups as $group)
                                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="fabric_width_unit" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 font-body-md px-2 py-2.5 bg-surface font-bold text-xs truncate">
                                            @foreach($allUnitGroups->find($fabric_width_group_id)?->units ?? [] as $unit)
                                                <option value="{{ $unit->short_code }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('standard_fabric_width') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <!-- Length & Unit -->
                                <div>
                                    <label class="block font-label-md text-on-surface-variant text-xs font-bold uppercase tracking-wider mb-2">Standard Length *</label>
                                    <div class="flex gap-2 min-w-0">
                                        <input wire:model.blur="standard_fabric_length" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-3.5 py-2.5 bg-surface text-sm font-semibold" type="number" step="0.01" placeholder="2.50"/>
                                        <select wire:model.live="fabric_length_group_id" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 font-body-md px-2 py-2.5 bg-surface font-bold text-xs truncate">
                                            @foreach($allUnitGroups as $group)
                                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="fabric_length_unit" class="flex-1 min-w-0 rounded-xl border border-outline-variant/60 font-body-md px-2 py-2.5 bg-surface font-bold text-xs truncate">
                                            @foreach($allUnitGroups->find($fabric_length_group_id)?->units ?? [] as $unit)
                                                <option value="{{ $unit->short_code }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('standard_fabric_length') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Image Upload Container -->
                    <div class="flex flex-col items-center justify-center border-2 border-dashed border-outline-variant/60 rounded-2xl p-6 sm:p-8 bg-surface-container-low/20 group hover:border-primary transition-all relative overflow-hidden">
                        <input type="file" id="product-image-upload" wire:model="imageUpload" class="hidden" accept="image/png, image/jpeg, image/jpg, image/webp" />

                        @if($imageUpload)
                            <div class="relative w-full aspect-video max-w-md rounded-xl overflow-hidden border border-outline-variant/40 shadow-sm group/img">
                                <img src="{{ $imageUpload->temporaryUrl() }}" class="w-full h-full object-cover" />
                                <button type="button" wire:click="$set('imageUpload', null)" class="absolute top-2 right-2 bg-error text-white rounded-full p-1 shadow-md hover:bg-error/90 transition-colors cursor-pointer" title="Remove image">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                        @elseif(!empty($existing_image_path))
                            <div class="relative w-full aspect-video max-w-md rounded-xl overflow-hidden border border-outline-variant/40 shadow-sm group/img">
                                <img src="{{ Storage::url($existing_image_path) }}" class="w-full h-full object-cover" />
                                <label for="product-image-upload" class="absolute bottom-2 right-2 bg-surface text-primary rounded-lg px-2.5 py-1 text-xs font-bold shadow-md hover:bg-surface-container transition-colors cursor-pointer flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                    Change Image
                                </label>
                            </div>
                        @else
                            <label for="product-image-upload" class="cursor-pointer flex flex-col items-center justify-center w-full h-full select-none">
                                <div class="w-14 h-14 bg-surface-container-high rounded-full flex items-center justify-center text-primary mb-3 group-hover:scale-110 transition-transform shadow-xs">
                                    <span class="material-symbols-outlined text-2xl">add_photo_alternate</span>
                                </div>
                                <p class="font-body-md font-bold text-center text-sm text-on-surface">Upload Product Image</p>
                                <p class="font-label-sm text-on-surface-variant text-center mt-1 text-xs">PNG, JPG up to 10MB. Recommended resolution 800x800.</p>
                                <span class="mt-3 px-4 py-2 border border-outline-variant rounded-lg font-label-md text-xs hover:bg-surface-container transition-colors font-bold text-primary bg-surface shadow-xs inline-block">Browse Files</span>
                            </label>
                        @endif

                        @error('imageUpload')
                            <span class="text-error text-xs block mt-2 font-semibold text-center">{{ $message }}</span>
                        @enderror

                        <div wire:loading wire:target="imageUpload" class="absolute inset-0 bg-surface-container-lowest/80 backdrop-blur-xs flex items-center justify-center gap-2">
                            <span class="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
                            <span class="text-xs font-bold text-primary">Uploading...</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($wizardStep === 2)
            <!-- ═══════════════ CARD 2: SUBSIDIARY MATERIAL BOM ═══════════════ -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <!-- Card Header / Toggle Row -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-secondary-container text-lg">inventory_2</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm">Subsidiary Material Configuration</h3>
                            <p class="text-on-surface-variant text-xs">BOM-linked items (Buttons, Zippers, Elastic, Labels…)</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" wire:model.live="is_subsidiary_used" class="sr-only peer"/>
                        <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                        <span class="font-label-md text-xs font-bold {{ $is_subsidiary_used ? 'text-secondary' : 'text-on-surface-variant' }}">
                            {{ $is_subsidiary_used ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>

                @if($is_subsidiary_used)
                    <div class="p-6 space-y-4">
                        <!-- Column Headers -->
                        <div class="grid grid-cols-12 gap-3 px-1">
                            <div class="col-span-5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Material (CAT-SUB)</div>
                            <div class="col-span-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Qty / Unit of Product</div>
                            <div class="col-span-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Auto-Derived Unit</div>
                            <div class="col-span-1"></div>
                        </div>

                        @error('subsidiaryMaterialsList')
                            <div class="text-error text-xs font-semibold bg-error-container/20 border border-error/20 rounded-lg px-4 py-2">{{ $message }}</div>
                        @enderror

                        <!-- Repeater Rows -->
                        @foreach($subsidiaryMaterialsList as $index => $row)
                            <div class="grid grid-cols-12 gap-3 items-start bg-surface-container-low/30 rounded-xl p-3 border border-outline-variant/40 group hover:border-secondary/40 transition-colors">
                                <!-- Raw Material Dropdown (CAT-SUB only) -->
                                <div class="col-span-5">
                                    <select
                                        wire:model.live="subsidiaryMaterialsList.{{ $index }}.raw_material_id"
                                        class="w-full rounded-lg border border-outline-variant/60 focus:border-secondary focus:ring-1 focus:ring-secondary font-body-md px-3 py-2.5 bg-surface text-sm"
                                    >
                                        <option value="">-- Select Material --</option>
                                        @foreach($subsidiaryRawMaterials as $mat)
                                            <option value="{{ $mat->id }}" {{ ($row['raw_material_id'] == $mat->id) ? 'selected' : '' }}>
                                                {{ $mat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("subsidiaryMaterialsList.{$index}.raw_material_id")
                                        <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Consumption Quantity -->
                                <div class="col-span-3">
                                    <input
                                        wire:model.blur="subsidiaryMaterialsList.{{ $index }}.consumption_quantity"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        placeholder="e.g. 4.0000"
                                        class="w-full rounded-lg border border-outline-variant/60 focus:border-secondary focus:ring-1 focus:ring-secondary font-body-md px-3 py-2.5 bg-surface text-sm"
                                    />
                                    @error("subsidiaryMaterialsList.{$index}.consumption_quantity")
                                        <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Auto-Derived Unit Badge -->
                                <div class="col-span-3 flex items-center h-full pt-0.5">
                                    @if(!empty($row['unit']))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-secondary-container/60 text-on-secondary-container font-bold text-xs border border-secondary/20">
                                            <span class="material-symbols-outlined text-[14px]">verified</span>
                                            {{ $row['unit'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface-variant/50 text-xs border border-outline-variant/30 italic">
                                            <span class="material-symbols-outlined text-[14px]">hourglass_empty</span>
                                            Select material
                                        </span>
                                    @endif
                                </div>

                                <!-- Delete Row Button -->
                                <div class="col-span-1 flex items-center justify-center h-full pt-0.5">
                                    <button
                                        type="button"
                                        wire:click="removeSubsidiaryRow({{ $index }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-error/60 hover:text-error hover:bg-error-container/20 transition-colors"
                                        title="Remove row"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete_outline</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <!-- Add Row Button -->
                        <button
                            type="button"
                            wire:click="addSubsidiaryRow"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-secondary/40 text-secondary font-bold text-xs hover:border-secondary hover:bg-secondary-container/20 transition-all w-full justify-center"
                        >
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                            Add Subsidiary Material Row
                        </button>

                        <div class="bg-tertiary-container/20 border border-tertiary/20 rounded-xl px-4 py-3 flex items-start gap-2">
                            <span class="material-symbols-outlined text-tertiary text-[16px] mt-0.5 flex-shrink-0">info</span>
                            <p class="text-xs text-on-surface-variant">
                                <strong class="text-on-surface">Note:</strong> Unit names are auto-inherited from the Raw Material Master and cannot be manually overridden. Consumption is deducted per actual batch executed with no wastage multiplier.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="px-6 py-8 flex flex-col items-center justify-center text-center">
                        <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                        <p class="text-sm text-on-surface-variant">Enable the toggle above to configure Bill of Materials for subsidiary items (buttons, zippers, elastic, labels, etc.)</p>
                    </div>
                @endif
            </div>
            @endif

            @if($wizardStep === 3)
            <!-- ═══════════════ CARD 3: STITCHING MATERIAL CONFIG ═══════════════ -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-tertiary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-tertiary-container text-lg">content_cut</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm">Stitching Material Configuration</h3>
                            <p class="text-on-surface-variant text-xs">Link thread & stitching consumables (CAT-STITCH) for cost pool accumulation.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" wire:model.live="is_stitching_used" class="sr-only peer"/>
                        <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tertiary"></div>
                        <span class="font-label-md text-xs font-bold {{ $is_stitching_used ? 'text-tertiary' : 'text-on-surface-variant' }}">
                            {{ $is_stitching_used ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>

                @if($is_stitching_used)
                    <div class="p-6 space-y-5">
                        @if($stitchingRawMaterials->isEmpty())
                            <div class="bg-warning-container/20 border border-warning/30 rounded-xl px-4 py-4 flex items-start gap-2">
                                <span class="material-symbols-outlined text-warning text-[18px] mt-0.5 flex-shrink-0">warning</span>
                                <div>
                                    <p class="font-bold text-sm">No Stitching Materials Found</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">
                                        Please add Raw Materials under category <code class="bg-surface-container-high px-1.5 py-0.5 rounded font-mono text-[10px]">CAT-STITCH</code> in the Raw Material Master before configuring this product.
                                    </p>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Select Stitching Materials used in this product</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($stitchingRawMaterials as $sMat)
                                    @php $checked = in_array((string)$sMat->id, $stitchingMaterialsList); @endphp
                                    <label
                                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all {{ $checked ? 'border-tertiary bg-tertiary-container/30' : 'border-outline-variant/40 bg-surface-container-low/20 hover:border-tertiary/50' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model.live="stitchingMaterialsList"
                                            value="{{ $sMat->id }}"
                                            class="w-4 h-4 rounded border-outline-variant text-tertiary focus:ring-tertiary"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-sm truncate">{{ $sMat->name }}</p>
                                            <p class="text-xs text-on-surface-variant font-mono">{{ $sMat->code }} · {{ $sMat->unit }}</p>
                                        </div>
                                        @if($checked)
                                            <span class="material-symbols-outlined text-tertiary text-[18px] flex-shrink-0">check_circle</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            <div class="bg-tertiary-container/20 border border-tertiary/20 rounded-xl px-4 py-3 flex items-start gap-2">
                                <span class="material-symbols-outlined text-tertiary text-[16px] mt-0.5 flex-shrink-0">info</span>
                                <p class="text-xs text-on-surface-variant">
                                    <strong class="text-on-surface">Note:</strong> Stitching materials are <em>not</em> directly deducted per unit during job execution. All stitching costs accumulate into a periodic cost pool for period-end costing allocation. No wastage calculation applies.
                                </p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="px-6 py-8 flex flex-col items-center justify-center text-center">
                        <span class="material-symbols-outlined text-4xl text-outline mb-2">content_cut</span>
                        <p class="text-sm text-on-surface-variant">Enable the toggle above to link stitching threads and consumables from the CAT-STITCH category to this product for cost pool tracking.</p>
                    </div>
                @endif
            </div>
            @endif

            @if($wizardStep === 4)
            <!-- ═══════════════ CARD 4: TASK SEQUENCE & ROUTING CONFIGURATION ═══════════════ -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">account_tree</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-primary">Manufacturing Task Sequence & Routing Editor</h3>
                            <p class="text-on-surface-variant text-xs">Define step-by-step manufacturing process routing, stage labor rates, and final step flag.</p>
                        </div>
                    </div>
                    <span class="bg-primary/10 text-primary px-3 py-1 rounded-full font-mono text-xs font-bold">
                        {{ count($routingTasksList) }} Steps Configured
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    @error('routingTasksList')
                        <div class="text-error text-xs font-semibold bg-error-container/20 border border-error/20 rounded-lg px-4 py-2">{{ $message }}</div>
                    @enderror

                    <!-- Column Headers -->
                    <div class="grid grid-cols-12 gap-3 px-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                        <div class="col-span-1 text-center">Seq</div>
                        <div class="col-span-5">Task Stage</div>
                        <div class="col-span-3">Stage Labor Rate (₹)</div>
                        <div class="col-span-2 text-center">Final Step</div>
                        <div class="col-span-1 text-center">Reorder</div>
                    </div>

                    <!-- Sequence Repeater Rows -->
                    @foreach($routingTasksList as $index => $rRow)
                        <div class="grid grid-cols-12 gap-3 items-center bg-surface-container-low/30 rounded-xl p-3 border border-outline-variant/40 group hover:border-primary/40 transition-colors">
                            <!-- Sequence Number Badge -->
                            <div class="col-span-1 flex items-center justify-center">
                                <span class="w-7 h-7 rounded-full bg-primary text-on-primary font-mono font-black text-xs flex items-center justify-center shadow-xs">
                                    {{ $index + 1 }}
                                </span>
                            </div>

                            <!-- Task Selection Dropdown -->
                            <div class="col-span-5">
                                <select
                                    wire:model.live="routingTasksList.{{ $index }}.task_id"
                                    class="w-full rounded-lg border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-3 py-2 bg-surface text-sm font-semibold"
                                >
                                    <option value="">-- Select Stage Task --</option>
                                    @foreach($availableTasks as $t)
                                        <option value="{{ $t->id }}">
                                            {{ $t->name }} ({{ $t->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error("routingTasksList.{$index}.task_id")
                                    <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Standard Labor Rate for Task -->
                            <div class="col-span-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-outline">₹</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.blur="routingTasksList.{{ $index }}.standard_labor_rate"
                                        placeholder="15.00"
                                        class="w-full rounded-lg border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md pl-7 pr-3 py-2 bg-surface text-sm font-bold text-primary"
                                    />
                                </div>
                                @error("routingTasksList.{$index}.standard_labor_rate")
                                    <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Is Final Step Radio Toggle -->
                            <div class="col-span-2 flex flex-col items-center justify-center">
                                <button
                                    type="button"
                                    wire:click="setFinalStep({{ $index }})"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all flex items-center gap-1 {{ !empty($rRow['is_final_step']) ? 'bg-secondary-container text-on-secondary-container border-secondary/40 shadow-xs' : 'bg-surface border-outline-variant/50 text-on-surface-variant opacity-60 hover:opacity-100' }}"
                                >
                                    <span class="material-symbols-outlined text-[14px]">{{ !empty($rRow['is_final_step']) ? 'task_alt' : 'radio_button_unchecked' }}</span>
                                    <span>{{ !empty($rRow['is_final_step']) ? 'Final Step' : 'Step' }}</span>
                                </button>
                            </div>

                            <!-- Reorder / Actions -->
                            <div class="col-span-1 flex items-center justify-center gap-1">
                                <div class="flex flex-col">
                                    @if($index > 0)
                                        <button type="button" wire:click="moveRoutingRow({{ $index }}, 'up')" class="text-on-surface-variant hover:text-primary p-0.5" title="Move Up">
                                            <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                                        </button>
                                    @endif
                                    @if($index < count($routingTasksList) - 1)
                                        <button type="button" wire:click="moveRoutingRow({{ $index }}, 'down')" class="text-on-surface-variant hover:text-primary p-0.5" title="Move Down">
                                            <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                                        </button>
                                    @endif
                                </div>
                                @if(count($routingTasksList) > 1 && $index > 0)
                                    <button
                                        type="button"
                                        wire:click="removeRoutingRow({{ $index }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-error/60 hover:text-error hover:bg-error-container/20 transition-colors ml-1"
                                        title="Remove step"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">delete_outline</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <!-- Add Step Button -->
                    <button
                        type="button"
                        wire:click="addRoutingRow"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-primary/40 text-primary font-bold text-xs hover:border-primary hover:bg-primary-container/20 transition-all w-full justify-center"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        Add Manufacturing Task Step
                    </button>

                    <!-- Routing Note -->
                    <div class="bg-primary-container/20 border border-primary/20 rounded-xl px-4 py-3 flex items-start gap-2">
                        <span class="material-symbols-outlined text-primary text-[16px] mt-0.5 flex-shrink-0">info</span>
                        <p class="text-xs text-on-surface-variant">
                            <strong class="text-on-surface">Note:</strong> Exactly one task in the sequence must be designated as the <strong>Final Production Step</strong>. Completing the final step automatically marks the entire Production Batch as completed. Downstream jobs are generated sequentially upon completion of each stage.
                        </p>
                    </div>
                </div>
            </div>

            @endif

        </div>

        <!-- Sticky Footer -->
        <div class="col-span-12 mt-6 flex justify-between items-center py-4 px-6 bg-surface-container-low border border-outline-variant/60 rounded-2xl shadow-xs">
            <div class="flex items-center gap-2 text-on-surface-variant font-label-sm text-xs">
                @if($wizardStep > 1)
                    <button type="button" wire:click="previousStep" class="px-6 py-2.5 rounded-xl font-label-md text-label-md font-bold text-on-surface-variant hover:bg-surface-container-highest transition-colors duration-150 text-xs flex items-center gap-1 border border-outline-variant/30">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Previous Step
                    </button>
                @else
                    <a href="{{ route('factory.products.index') }}" wire:navigate class="px-6 py-2.5 rounded-xl font-label-md text-label-md font-bold text-on-surface-variant hover:bg-surface-container-highest transition-colors duration-150 text-xs">
                        Cancel
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($wizardStep < $maxSteps)
                    <button type="button" wire:click="nextStep" class="px-8 py-2.5 bg-primary text-on-primary rounded-xl font-label-md text-label-md font-bold shadow-md hover:brightness-110 active:scale-95 transition-all duration-150 flex items-center gap-2 text-xs">
                        Next Step
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </button>
                @else
                    <button type="submit" class="px-8 py-2.5 bg-secondary text-white rounded-xl font-label-md text-label-md font-bold shadow-md hover:brightness-110 active:scale-95 transition-all duration-150 flex items-center gap-2 text-xs">
                        <span class="material-symbols-outlined text-[18px]">bolt</span>
                        {{ $productId ? 'Save Changes' : 'Save Product' }}
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
