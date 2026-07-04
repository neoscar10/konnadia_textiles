{{--
    Reusable Product Wizard Modal Partial
    Include from any Livewire component that has the full wizard state/methods.

    Variables:
      $modalId          - the modal id string (e.g. 'add-product' or 'cat-add-product')
      $deleteModalId    - the delete confirm modal id
      $valueMediaModalId - the variation media modal id
      $lockedCategory   - (optional) Category model; if set, category step shows a locked badge
--}}

<x-admin.modal id="{{ $modalId }}" title="{{ $selectedProductId ? 'Edit Product' : 'Add New Product' }}" maxWidth="5xl">
    <!-- Stepper Navigation -->
    <div class="border-b border-outline-variant/20 px-xl py-md bg-surface-container-low flex flex-nowrap items-center justify-between gap-md overflow-x-auto whitespace-nowrap select-none">
        @php
            $steps = [
                1 => 'Basic Info',
                2 => 'Media',
                3 => 'Categories',
                4 => 'Pricing & Units',
                5 => 'Review'
            ];
        @endphp
        @foreach($steps as $num => $title)
            <button type="button" x-on:click="$wire.call('selectStep', {{ $num }})" class="flex items-center gap-xs font-label-md transition-all focus:outline-none {{ $currentStep == $num ? 'text-primary font-bold' : ($currentStep > $num ? 'text-secondary' : 'text-on-surface-variant/40') }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep == $num ? 'bg-primary text-on-primary' : ($currentStep > $num ? 'bg-secondary-container text-on-secondary-container' : 'bg-outline-variant/30') }}">
                    {{ $num }}
                </span>
                <span class="hidden md:inline">{{ $title }}</span>
            </button>
        @endforeach
    </div>

    <!-- Wizard Steps Content -->
    <div class="p-xl overflow-y-auto max-h-[550px]" style="min-height: 400px;">

        <!-- STEP 1: Basic Info -->
        @if($currentStep === 1)
            <div class="space-y-lg">
                {{-- Warning for existing products with missing GST --}}
                @if($selectedProductId && $basicInfo['gst_percentage'] === '')
                    <div class="flex items-start gap-sm p-md rounded-lg bg-warning/10 border border-warning/30">
                        <span class="material-symbols-outlined text-warning text-[20px] shrink-0 mt-0.5">warning</span>
                        <div>
                            <p class="font-label-md text-on-surface font-bold">GST Not Configured</p>
                            <p class="font-body-sm text-on-surface-variant">This product is missing a GST percentage. Customers will not be able to purchase it until GST is set. Please enter the applicable rate below.</p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">Product Title *</label>
                        <input type="text" wire:model="basicInfo.title" placeholder="e.g. Classic Premium Linen Shirt" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        @error('basicInfo.title') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">Base Price (MRP in INR) *</label>
                        <div class="relative">
                            <span class="absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">₹</span>
                            <input type="number" step="0.01" wire:model="basicInfo.base_price" placeholder="0.00" class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        </div>
                        @error('basicInfo.base_price') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">HSN Code</label>
                        <input type="text" wire:model="basicInfo.hsn_code" placeholder="e.g. 6205" maxlength="20" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface font-mono">
                        <p class="text-xs text-on-surface-variant/70">Enter the applicable HSN code for this product if available.</p>
                        @error('basicInfo.hsn_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">GST Percentage *</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" wire:model="basicInfo.gst_percentage" placeholder="e.g. 12" class="w-full pr-xl px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">%</span>
                        </div>
                        <p class="text-xs text-on-surface-variant/70">Enter the GST % for this product. Used for cart, checkout, and order tax calculation. Enter <strong>0</strong> for zero-rated products.</p>
                        @error('basicInfo.gst_percentage') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">Min Order Qty *</label>
                        <input type="number" min="1" step="1" wire:model="basicInfo.minimum_order_quantity" placeholder="e.g. 1" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        <p class="text-xs text-on-surface-variant/70">Minimum quantity a customer must add to their cart.</p>
                        @error('basicInfo.minimum_order_quantity') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant">Product Type *</label>
                        <select wire:model.live="basicInfo.product_type" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            <option value="retail">Manufactured </option>
                            <option value="manufactured">Retail / Bought</option>
                        </select>
                        @error('basicInfo.product_type') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Markdown Description Editor -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant select-none">Description</label>
                    <div class="border border-outline-variant/60 rounded-lg overflow-hidden bg-white shadow-sm">
                        <!-- Quick Markup toolbar -->
                        <div class="px-md py-xs border-b border-outline-variant/30 bg-surface-container-low/40 flex items-center gap-md select-none flex-wrap">
                            <div class="flex items-center gap-xs">
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '**' + text.substring(start, end) + '**' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-extrabold text-sm text-primary" title="Bold">B</button>
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '*' + text.substring(start, end) + '*' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container italic font-bold text-sm text-primary" title="Italic">I</button>
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '### ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-bold text-sm text-primary" title="Heading">H</button>
                            </div>
                            <div class="w-px h-5 bg-outline-variant/40"></div>
                            <div class="flex items-center gap-xs">
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '> ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary text-base font-bold" title="Quote">"</button>
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '- ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Bullet List">
                                    <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                </button>
                                <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '1. ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Numbered List">
                                    <span class="material-symbols-outlined text-[20px]">format_list_numbered</span>
                                </button>
                            </div>
                            <div class="w-px h-5 bg-outline-variant/40"></div>
                            <div class="flex items-center gap-xs">
                                <button type="button" wire:click="$toggle('isPreviewMode')" class="w-8 h-8 rounded flex items-center justify-center {{ $isPreviewMode ? 'bg-secondary/15 text-secondary' : 'text-primary hover:bg-surface-container' }} flex items-center justify-center" title="Toggle Preview">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </div>
                        </div>

                        @if(!$isPreviewMode)
                            <textarea id="desc-editor-wiz" rows="6" wire:model="basicInfo.description" placeholder="Enter text..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[160px]"></textarea>
                        @else
                            <div class="prose max-w-none p-md min-h-[160px] bg-surface-container-low/20 text-on-surface text-sm overflow-y-auto">
                                {!! Illuminate\Support\Str::markdown($basicInfo['description'] ?? '*Enter text...*') !!}
                            </div>
                        @endif
                    </div>
                    @error('basicInfo.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        <!-- STEP 2: Media Upload -->
        @if($currentStep === 2)
            <style>
                .bg-checkered {
                    background-color: #ffffff;
                    background-image:
                        linear-gradient(45deg, #efefef 25%, transparent 25%),
                        linear-gradient(-45deg, #efefef 25%, transparent 25%),
                        linear-gradient(45deg, transparent 75%, #efefef 75%),
                        linear-gradient(-45deg, transparent 75%, #efefef 75%);
                    background-size: 16px 16px;
                    background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
                }
            </style>
            <div class="space-y-xl">
                <!-- Drag and Drop Zone -->
                <div x-data="{ dragging: false }"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="dragging = false; $wire.upload('mediaUploads', $event.dataTransfer.files)"
                     class="border border-outline-variant/60 rounded-xl p-xl flex flex-col items-center justify-center text-center transition-all bg-surface-container-low/20 select-none relative"
                     :class="dragging ? 'border-primary bg-primary/5' : 'hover:bg-surface-container-low/30'">
                    <input type="file" multiple id="media-uploader-wiz" class="hidden" wire:model="mediaUploads" accept="image/png, image/jpeg, image/jpg, image/webp">
                    <label for="media-uploader-wiz" class="cursor-pointer flex flex-col items-center">
                        <span class="material-symbols-outlined text-[44px] text-on-surface-variant/70 mb-xs select-none">cloud_upload</span>
                        <span class="font-bold text-on-surface text-lg mb-xxs">Product Gallery</span>
                        <span class="text-xs text-on-surface-variant mb-md">Upload high-quality images for the product.</span>
                        <span class="px-md py-sm bg-[#5c44c4] hover:bg-[#4d37a8] text-white rounded-lg font-semibold text-sm transition-all shadow-sm select-none">Add Images</span>
                        <span class="text-xs text-[#0284c7] font-semibold flex items-center gap-xxs mt-md select-none bg-[#f0f9ff] px-md py-xs rounded border border-[#bae6fd]/30">
                            <span class="material-symbols-outlined text-[16px]">info</span>
                            Recommended: 1080 &times; 1080
                        </span>
                    </label>
                </div>

                <!-- Loader for Image Upload -->
                <div wire:loading wire:target="mediaUploads" class="w-full bg-surface-container-low/40 border border-outline-variant/20 rounded-xl p-md flex items-center justify-center gap-sm">
                    <svg class="animate-spin h-6 w-6 text-[#5c44c4]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-bold text-primary animate-pulse">Uploading and processing images...</span>
                </div>

                <!-- Gallery Grid -->
                @if(!empty($existingMedia) || !empty($mediaUploads))
                    <div class="space-y-md">
                        @if(!empty($existingMedia))
                            <div wire:ignore.self x-data
                                 x-init="$nextTick(() => {
                                     const grid = $el.querySelector('.sortable-grid-wiz');
                                     if (grid && typeof Sortable !== 'undefined') {
                                         new Sortable(grid, {
                                             animation: 250, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
                                             filter: '.no-drag', preventOnFilter: false,
                                             onEnd: function(evt) { if (evt.oldIndex !== evt.newIndex) { $wire.call('reorderExistingMediaInArray', evt.oldIndex, evt.newIndex); } }
                                         });
                                     }
                                 })">
                                <div class="sortable-grid-wiz grid grid-cols-2 sm:grid-cols-4 gap-lg select-none">
                                    @foreach($existingMedia as $index => $m)
                                        <div class="drag-handle border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 cursor-grab active:cursor-grabbing transition-all duration-200 shadow-sm hover:shadow-md hover:scale-[1.02]"
                                             wire:key="existing-media-wiz-{{ $m['id'] }}">
                                            <img src="{{ Storage::url($m['file_path']) }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false">
                                            <span class="absolute top-2 left-2 text-white/90 opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg pointer-events-none">
                                                <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
                                            </span>
                                            <span class="absolute top-2 left-8 bg-black/50 text-white text-[10px] font-bold rounded px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">{{ $index + 1 }}</span>
                                            <button type="button" x-on:click.stop.prevent="$wire.call('removeExistingMedia', {{ $m['id'] }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-7 h-7 flex items-center justify-center absolute top-2 right-2 shadow z-10 transition-colors cursor-pointer" title="Delete">
                                                <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                            </button>
                                            @if($m['is_primary'])
                                                <span class="absolute bottom-2 left-2 px-sm py-xxs bg-[#5c44c4] text-on-primary text-[10px] font-bold rounded shadow-sm select-none pointer-events-none">COVER</span>
                                            @else
                                                <button type="button" x-on:click.stop.prevent="$wire.call('setPrimaryMedia', {{ $m['id'] }})" class="absolute bottom-2 left-2 px-sm py-xxs bg-white/95 text-[#5c44c4] text-[10px] font-bold rounded shadow-sm select-none opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white cursor-pointer">SET COVER</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($mediaUploads))
                            <div wire:ignore.self x-data
                                 x-init="$nextTick(() => {
                                     const grid = $el.querySelector('.sortable-uploads-wiz');
                                     if (grid && typeof Sortable !== 'undefined') {
                                         new Sortable(grid, {
                                             animation: 250, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
                                             filter: '.no-drag', preventOnFilter: false,
                                             onEnd: function(evt) { if (evt.oldIndex !== evt.newIndex) { $wire.call('reorderUploadedMedia', evt.oldIndex, evt.newIndex); } }
                                         });
                                     }
                                 })">
                                <div class="sortable-uploads-wiz grid grid-cols-2 sm:grid-cols-4 gap-lg select-none">
                                    @foreach($mediaUploads as $index => $file)
                                        <div class="border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200 hover:shadow-md hover:scale-[1.02]" wire:key="new-upload-wiz-{{ $index }}">
                                            @php $tempUrl = null; try { $tempUrl = $file->temporaryUrl(); } catch (\Exception $e) {} @endphp
                                            @if($tempUrl)
                                                <img src="{{ $tempUrl }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false" />
                                            @else
                                                <div class="flex flex-col items-center justify-center text-on-surface-variant/50">
                                                    <span class="material-symbols-outlined text-3xl">image</span>
                                                </div>
                                            @endif
                                            <span class="absolute top-2 left-2 text-white/90 opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg pointer-events-none">
                                                <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
                                            </span>
                                            <span class="absolute top-2 left-8 bg-black/50 text-white text-[10px] font-bold rounded px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">{{ $index + 1 }}</span>
                                            <button type="button" x-on:click.stop.prevent="$wire.call('deleteUploadedFile', {{ $index }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-7 h-7 flex items-center justify-center absolute top-2 right-2 shadow z-10 transition-colors cursor-pointer" title="Remove">
                                                <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                            </button>
                                            <span class="absolute bottom-2 left-2 px-sm py-xxs bg-secondary text-on-secondary text-[10px] font-bold rounded shadow-sm select-none">NEW</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <!-- STEP 3: Categories -->
        @if($currentStep === 3)
            <div class="space-y-lg select-none">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-title-md text-primary">Assign Categories *</h4>
                        @if(isset($lockedCategory) && $lockedCategory)
                            <p class="text-xs text-on-surface-variant">
                                Category is locked to
                                <span class="font-bold text-secondary">{{ $lockedCategory->name }}</span>
                                because you're adding from within that category.
                            </p>
                        @else
                            <p class="text-xs text-on-surface-variant">Assign this product to one or multiple leaf categories.</p>
                        @endif
                    </div>
                    @if(!empty($selectedCategoryIds))
                        @php
                            $unlockableIds = isset($lockedCategory) && $lockedCategory
                                ? array_diff($selectedCategoryIds, [$lockedCategory->id])
                                : $selectedCategoryIds;
                        @endphp
                        @if(!empty($unlockableIds))
                            <button type="button" wire:click="$set('selectedCategoryIds', {{ json_encode($lockedCategory ? [$lockedCategory->id] : []) }})" class="text-xs text-error font-bold hover:underline">Clear All</button>
                        @endif
                    @endif
                </div>

                <!-- Locked Category Badge -->
                @if(isset($lockedCategory) && $lockedCategory)
                    <div class="flex items-center gap-sm p-sm bg-secondary/5 border border-secondary/30 rounded-lg">
                        <span class="material-symbols-outlined text-secondary text-[20px]">lock</span>
                        <div>
                            <p class="font-label-md text-secondary">Locked Category: <strong>{{ $lockedCategory->name }}</strong></p>
                            <p class="text-xs text-on-surface-variant">This product will always belong to this category. You may optionally add additional categories below.</p>
                        </div>
                    </div>
                @endif

                <!-- Selected categories list -->
                @if(!empty($selectedCategoryIds))
                    <div class="flex flex-wrap gap-xs p-sm bg-surface-container/30 border border-outline-variant/20 rounded-lg">
                        @foreach($selectedCategoryIds as $catId)
                            @php $catModel = $categories->firstWhere('id', $catId); @endphp
                            @if($catModel)
                                <span class="inline-flex items-center gap-xxs px-sm py-xxs bg-[#5c44c4]/10 text-[#5c44c4] text-xs font-bold rounded-full border border-[#5c44c4]/20">
                                    {{ $catModel->name }}
                                    @if(!isset($lockedCategory) || !$lockedCategory || (int)$catId !== $lockedCategory->id)
                                        <button type="button" x-on:click="$wire.call('removeCategory', {{ $catId }})" class="hover:text-error text-sm font-bold ml-1">&times;</button>
                                    @else
                                        <span class="material-symbols-outlined text-[12px] ml-xxs opacity-60">lock</span>
                                    @endif
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                <!-- Leaf categories flat checklist -->
                <div class="border border-outline-variant/30 rounded-lg p-lg max-h-[300px] overflow-y-auto bg-surface-container-low custom-scrollbar divide-y divide-outline-variant/10">
                    @php
                        $categoriesById = $categories->keyBy('id');
                        $buildPath = function($cat) use ($categoriesById) {
                            $path = [$cat->name];
                            $current = $cat;
                            while ($current->parent_id && isset($categoriesById[$current->parent_id])) {
                                $current = $categoriesById[$current->parent_id];
                                array_unshift($path, $current->name);
                            }
                            return $path;
                        };
                        // Filter active leaf categories and sort them by path
                        $leafCats = $categories->where('is_leaf', true)->where('is_active', true)->sortBy(fn($cat) => implode(' > ', $buildPath($cat)));
                    @endphp
                    @foreach($leafCats as $leaf)
                        @php
                            $pathSegments = $buildPath($leaf);
                        @endphp
                        <div class="flex items-center gap-md py-sm px-sm hover:bg-surface-container/30 transition-colors select-none">
                            <input type="checkbox" id="cat_{{ $leaf->id }}" value="{{ $leaf->id }}" wire:model.live="selectedCategoryIds" class="w-4.5 h-4.5 rounded border-outline-variant text-[#5c44c4] focus:ring-[#5c44c4] cursor-pointer">
                            
                            <label for="cat_{{ $leaf->id }}" class="flex items-center flex-wrap gap-xs text-sm text-on-surface font-semibold cursor-pointer select-none">
                                @foreach($pathSegments as $index => $segment)
                                    @if($index > 0)
                                        <!-- Nice arrow icon -->
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant/40 shrink-0 select-none">chevron_right</span>
                                    @endif
                                    <span class="{{ $index === count($pathSegments) - 1 ? 'text-[#5c44c4] font-extrabold uppercase' : 'text-on-surface-variant/60 font-medium' }}">
                                        {{ $segment }}
                                    </span>
                                @endforeach
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('selectedCategoryIds') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- ======================================================
             STEP 4: Variations — COMMENTED OUT (not removed).
             Remove the comment tags below to re-enable this step.
             Remember to also restore steps array and step 5 below.
        ====================================================== --}}
        {{--
        @if($currentStep === 4)
            ... variation step content ...
        @endif
        --}}

        <!-- STEP 4: Pricing overrides & Units (was step 6) -->
        @if($currentStep === 4)
            <div class="space-y-xl">

                <!-- Stock Quantity -->
                <div class="space-y-md border-b border-outline-variant/20 pb-xl">
                    <h4 class="font-title-md text-primary">Stock Quantity</h4>
                    @if($basicInfo['product_type'] === 'retail')
                        <div class="flex items-start gap-sm p-sm rounded-lg bg-primary/5 border border-primary/20 select-none">
                            <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">inventory_2</span>
                            <div>
                                <p class="font-label-md text-primary">Manufactured Product &mdash; Stock tracking available</p>
                                <p class="text-xs text-on-surface-variant">You can define stock quantity or leave it empty to mark as <strong>N/A (Unlimited)</strong> &mdash; no stock restrictions will apply.</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-sm p-sm rounded-lg bg-secondary/5 border border-secondary/20 select-none">
                            <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">shopping_bag</span>
                            <div>
                                <p class="font-label-md text-secondary">Retail / Bought Product &mdash; Stock optional</p>
                                <p class="text-xs text-on-surface-variant">Stock tracking is optional. Leave empty to mark as <strong>N/A (Unlimited)</strong> &mdash; customers can order any quantity with no stock restriction.</p>
                            </div>
                        </div>
                    @endif
                    <div class="max-w-md space-y-xs">
                        <label class="font-label-md text-on-surface-variant">Total Stock <span class="text-on-surface-variant/60 font-normal">(leave empty for N/A / Unlimited)</span></label>
                        <div class="flex items-center gap-sm">
                            <input type="number" wire:model.live="nonVariantStock" placeholder="N/A" min="0" class="w-48 px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            @if($nonVariantStock !== '')
                                <span class="font-label-md text-on-surface font-bold">{{ number_format((int)$nonVariantStock) }} units tracked</span>
                            @else
                                <span class="flex items-center gap-xs text-xs text-on-surface-variant/80 bg-surface-container px-sm py-xs rounded-md border border-outline-variant/30">
                                    <span class="material-symbols-outlined text-[14px]">all_inclusive</span>
                                    N/A &mdash; Unlimited (no restriction)
                                </span>
                            @endif
                        </div>
                        @error('nonVariantStock') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Pricing overrides -->
                <div class="space-y-md">
                    <h4 class="font-title-md text-primary">Customer Level-Specific Discount Overrides</h4>
                    <p class="text-xs text-on-surface-variant">Define custom discounts (or markups) per level. Positive = discount off base price. <span class="text-error font-semibold">Negative = markup above base price</span> (e.g. -10 means customers in that level pay 10% more).</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg bg-surface-container-low/40 p-lg border border-outline-variant/20 rounded-lg">
                        @foreach($customerLevels as $level)
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">{{ $level->name }}</label>
                                <div class="flex items-center gap-xs">
                                    <div class="relative w-full">
                                        <input type="number" step="0.01" min="-100" max="100" wire:model="pricingOverrides.{{ $level->id }}" placeholder="Default: {{ $level->discount_percentage }}%" class="w-full pr-lg px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                        <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-sm">%</span>
                                    </div>
                                </div>
                                @error("pricingOverrides.{$level->id}") <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Unit setup -->
                <div class="space-y-lg border-t border-outline-variant/20 pt-lg">
                    <div>
                        <h4 class="font-title-md text-primary">Product Unit Configuration</h4>
                        <p class="text-xs text-on-surface-variant mt-xxs">Define how this product is measured and sold. Level 1 is the base unit (e.g. Piece). Level 2 is the larger grouping unit (e.g. Box). Leave Level 2 empty if the product is only sold in base units.</p>
                    </div>

                    <div class="flex flex-col items-stretch gap-sm">
                        <div class="flex flex-col sm:flex-row gap-sm items-stretch">
                            <!-- Level 1 -->
                            <div class="flex-1 bg-surface-container-low/60 border-2 border-primary/20 rounded-xl p-md space-y-md relative">
                                <div class="flex items-center gap-xs mb-sm select-none">
                                    <span class="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] font-bold flex items-center justify-center">1</span>
                                    <span class="font-label-md text-primary">Level 1 — Base Unit <span class="text-error">*</span></span>
                                </div>
                                <div class="grid grid-cols-2 gap-sm">
                                    <div class="space-y-xs">
                                        <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                                        <input type="text" wire:model.live="units.level1_name" placeholder="e.g. Piece" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm">
                                        @error('units.level1_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="space-y-xs">
                                        <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                                        <input type="text" wire:model.live="units.level1_code" placeholder="e.g. pcs" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                                        @error('units.level1_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="bg-primary/8 border border-primary/15 rounded-lg px-sm py-xs flex items-center gap-xs select-none">
                                    <span class="material-symbols-outlined text-primary text-[16px]">straighten</span>
                                    <span class="text-xs text-primary font-medium">
                                        Smallest unit — customers order in <strong>{{ $units['level1_name'] ?: '...' }}</strong> ({{ $units['level1_code'] ?: '...' }})
                                    </span>
                                </div>
                            </div>

                            <!-- Arrow connector -->
                            <div class="flex sm:flex-col items-center justify-center gap-xs px-sm text-on-surface-variant/50 select-none">
                                @if(!empty($units['level2_name']) && !empty($units['level2_conversion']))
                                    <div class="hidden sm:flex flex-col items-center gap-xs">
                                        <span class="material-symbols-outlined text-[28px] text-secondary">swap_vert</span>
                                        <div class="text-center">
                                            <div class="text-[10px] text-on-surface-variant leading-tight">1 {{ $units['level2_name'] ?: '...' }}</div>
                                            <div class="text-[10px] font-bold text-secondary leading-tight">= {{ $units['level2_conversion'] }}×</div>
                                        </div>
                                    </div>
                                    <div class="sm:hidden flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[24px] text-secondary">swap_horiz</span>
                                        <span class="text-xs font-bold text-secondary">1 {{ $units['level2_name'] }} = {{ $units['level2_conversion'] }} {{ $units['level1_name'] }}</span>
                                    </div>
                                @else
                                    <span class="material-symbols-outlined text-[24px] opacity-30">add_circle</span>
                                @endif
                            </div>

                            <!-- Level 2 -->
                            <div class="flex-1 bg-surface-container-low/40 border-2 {{ !empty($units['level2_name']) ? 'border-secondary/25' : 'border-dashed border-outline-variant/40' }} rounded-xl p-md space-y-md">
                                <div class="flex items-center gap-xs mb-sm select-none">
                                    <span class="w-5 h-5 rounded-full {{ !empty($units['level2_name']) ? 'bg-secondary text-on-secondary' : 'bg-outline-variant/40 text-on-surface-variant' }} text-[11px] font-bold flex items-center justify-center">2</span>
                                    <span class="font-label-md {{ !empty($units['level2_name']) ? 'text-secondary' : 'text-on-surface-variant/60' }}">Level 2 — Group Unit <span class="text-on-surface-variant/50 font-normal text-xs">(optional)</span></span>
                                </div>
                                <div class="grid grid-cols-2 gap-sm">
                                    <div class="space-y-xs">
                                        <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                                        <input type="text" wire:model.live="units.level2_name" placeholder="e.g. Box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm">
                                        @error('units.level2_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="space-y-xs">
                                        <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                                        <input type="text" wire:model.live="units.level2_code" placeholder="e.g. box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                                        @error('units.level2_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="space-y-xs">
                                    <label class="text-xs text-on-surface-variant font-medium">How many <strong>{{ $units['level1_name'] ?: 'base units' }}</strong> in 1 <strong>{{ $units['level2_name'] ?: 'group unit' }}</strong>?</label>
                                    <div class="flex items-center gap-sm">
                                        <div class="flex items-center gap-xs bg-white border border-outline-variant/50 rounded-lg px-sm py-xs focus-within:ring-2 focus-within:ring-secondary w-36">
                                            <span class="text-xs text-on-surface-variant select-none font-medium whitespace-nowrap">1 {{ $units['level2_name'] ?: '...' }} =</span>
                                            <input type="number" wire:model.live="units.level2_conversion" placeholder="qty" min="0.0001" step="any" class="w-16 bg-transparent border-none focus:ring-0 outline-none text-on-surface font-bold text-sm text-right">
                                        </div>
                                        <span class="text-sm font-bold text-on-surface-variant">{{ $units['level1_name'] ?: '...' }}</span>
                                    </div>
                                    @error('units.level2_conversion') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                </div>
                                @if(!empty($units['level2_name']) && !empty($units['level2_conversion']))
                                    <div class="bg-secondary/8 border border-secondary/15 rounded-lg px-sm py-xs flex items-center gap-xs select-none">
                                        <span class="material-symbols-outlined text-secondary text-[16px]">package_2</span>
                                        <span class="text-xs text-secondary font-medium">
                                            <strong>1 {{ $units['level2_name'] }} ({{ $units['level2_code'] ?: '...' }})</strong> = <strong>{{ $units['level2_conversion'] }} {{ $units['level1_name'] }}</strong>
                                            @if(!empty($basicInfo['base_price']))
                                                &nbsp;·&nbsp; Level 2 price: <strong>₹{{ number_format((float)$basicInfo['base_price'] * (float)$units['level2_conversion'], 2) }}</strong>
                                            @endif
                                        </span>
                                    </div>
                                @else
                                    <div class="bg-surface-container-low border border-dashed border-outline-variant/30 rounded-lg px-sm py-xs flex items-center gap-xs select-none opacity-60">
                                        <span class="material-symbols-outlined text-[16px]">info</span>
                                        <span class="text-xs text-on-surface-variant">Fill in Level 2 fields to enable group unit ordering</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!empty($units['level2_name']) && !empty($units['level2_conversion']))
                            <div class="bg-gradient-to-r from-primary/5 to-secondary/5 border border-outline-variant/20 rounded-xl px-lg py-md flex items-center gap-md select-none">
                                <span class="material-symbols-outlined text-primary text-[28px]">swap_horiz</span>
                                <div class="flex-1">
                                    <p class="font-label-md text-on-surface">Unit Relationship</p>
                                    <p class="text-sm font-bold text-primary">
                                        1 <span class="text-secondary">{{ $units['level2_name'] }}</span> ({{ $units['level2_code'] ?: '...' }})
                                        = {{ $units['level2_conversion'] }} <span class="text-primary">{{ $units['level1_name'] }}</span> ({{ $units['level1_code'] ?: '...' }})
                                    </p>
                                    <p class="text-xs text-on-surface-variant">Customers can order in individual <strong>{{ $units['level1_name'] }}</strong> or by the <strong>{{ $units['level2_name'] }}</strong>.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- STEP 5: Review -->
        @if($currentStep === 5)
            <div class="space-y-xl">
                <h3 class="font-title-lg text-primary select-none border-b pb-xs border-outline-variant/20">Summary Review</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md bg-surface-container-low/40 p-md rounded-lg border border-outline-variant/20">
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none">Title</span>
                        <span class="font-bold text-primary">{{ $basicInfo['title'] ?: 'Not Specified' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none">Product Type</span>
                        <span class="font-bold text-primary">
                            @if(($basicInfo['product_type'] ?? '') === 'retail')
                                Manufactured
                            @elseif(($basicInfo['product_type'] ?? '') === 'manufactured')
                                Retail / Bought
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none">Base Price</span>
                        <span class="font-bold text-primary">₹{{ number_format((float)($basicInfo['base_price'] ?: 0), 2) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none">HSN Code</span>
                        <span class="font-mono text-sm text-on-surface font-bold">{{ $basicInfo['hsn_code'] ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none">GST Percentage</span>
                        @if($basicInfo['gst_percentage'] !== '')
                            <span class="font-bold text-success">{{ $basicInfo['gst_percentage'] }}%</span>
                        @else
                            <span class="font-bold text-error flex items-center gap-xxs">
                                <span class="material-symbols-outlined text-[14px]">warning</span>
                                Missing — product cannot be sold until set
                            </span>
                        @endif
                    </div>
                    @if($selectedProductId)
                        @php $existingProduct = \App\Models\Product::find($selectedProductId); @endphp
                        @if($existingProduct)
                            <div class="md:col-span-2">
                                <span class="text-xs text-on-surface-variant block select-none">SKU</span>
                                <span class="font-mono text-sm text-primary font-bold">{{ $existingProduct->sku }}</span>
                            </div>
                        @endif
                    @endif
                    <div class="md:col-span-2">
                        <span class="text-xs text-on-surface-variant block select-none">Description</span>
                        <div class="prose max-w-none text-xs border p-sm rounded bg-white mt-xs">
                            {!! Illuminate\Support\Str::markdown($basicInfo['description'] ?? '') !!}
                        </div>
                    </div>
                </div>

                <!-- Categories Summary -->
                <div>
                    <span class="text-xs text-on-surface-variant block select-none mb-xs">Assigned Categories</span>
                    <div class="flex flex-wrap gap-xs">
                        @forelse($selectedCategoryIds as $catId)
                            @php $catModel = $categories->firstWhere('id', $catId); @endphp
                            @if($catModel)
                                <span class="px-sm py-xxs bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold">
                                    {{ $catModel->name }}
                                </span>
                            @endif
                        @empty
                            <span class="text-error text-xs font-semibold select-none">No categories assigned.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Pricing & Units Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg select-none">
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none mb-xs">Customer Pricing Details</span>
                        <table class="w-full text-left font-body-md text-xs border rounded-lg overflow-hidden">
                            <thead class="bg-surface-container text-on-surface-variant font-bold">
                                <tr>
                                    <th class="p-xs">Level</th>
                                    <th class="p-xs text-center">Discount</th>
                                    <th class="p-xs text-right">Selling Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerLevels as $level)
                                    @php
                                        $override = $pricingOverrides[$level->id] ?? '';
                                        $disc     = $override !== '' ? (float)$override : (float)$level->discount_percentage;
                                        $price    = (float)($basicInfo['base_price'] ?: 0) * (1 - ($disc / 100));
                                        $isMarkup = $disc < 0;
                                    @endphp
                                    <tr class="border-t">
                                        <td class="p-xs">{{ $level->name }}</td>
                                        <td class="p-xs text-center font-semibold {{ $isMarkup ? 'text-error' : 'text-success' }}">
                                            @if($isMarkup)
                                                +{{ number_format(abs($disc), 2) }}% markup
                                            @else
                                                {{ number_format($disc, 2) }}% discount
                                            @endif
                                            {{ $override !== '' ? '(Override)' : '(Default)' }}
                                        </td>
                                        <td class="p-xs text-right font-bold {{ $isMarkup ? 'text-error' : 'text-primary' }}">₹{{ number_format($price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <span class="text-xs text-on-surface-variant block select-none mb-xs">Units Setup</span>
                        <div class="p-md border rounded-lg bg-white space-y-xs">
                            <div>
                                <span class="text-[10px] text-on-surface-variant block">Primary Unit (Level 1)</span>
                                <span class="font-bold">{{ $units['level1_name'] }} ({{ $units['level1_code'] }})</span>
                            </div>
                            @if(!empty($units['level2_name']))
                                <div class="border-t pt-xs">
                                    <span class="text-[10px] text-on-surface-variant block">Secondary Unit (Level 2)</span>
                                    <span class="font-bold">{{ $units['level2_name'] }} ({{ $units['level2_code'] }})</span>
                                    <span class="text-xs text-on-surface-variant block">Conversion: 1 {{ $units['level2_name'] }} = {{ $units['level2_conversion'] }} {{ $units['level1_code'] }}</span>
                                    <span class="text-xs text-primary font-bold block">Level 2 Price: ₹{{ number_format((float)($basicInfo['base_price'] ?: 0) * (float)$units['level2_conversion'], 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Sticky Footer Buttons -->
    <x-slot name="footer" class="flex justify-between items-center select-none">
        <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
        <div class="flex gap-sm">
            @if($currentStep > 1)
                <x-admin.button variant="outline" type="button" wire:click="prevStep">Back</x-admin.button>
            @endif
            @if($isEditMode && $currentStep < 5)
                <x-admin.button variant="primary" type="button" wire:click="saveCurrentStep" icon="save">Save Changes</x-admin.button>
            @endif
            @if($currentStep < 5)
                <x-admin.button variant="primary" type="button" wire:click="nextStep">Next Step</x-admin.button>
            @endif
            @if($currentStep === 5)
                <x-admin.button variant="primary" type="button" wire:click="save" icon="save">
                    {{ $isEditMode ? 'Save Changes' : 'Create Product' }}
                </x-admin.button>
            @endif
        </div>
    </x-slot>
</x-admin.modal>
