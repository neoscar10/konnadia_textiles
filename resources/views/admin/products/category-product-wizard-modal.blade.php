{{--
    Simplified Product Wizard Modal for Category Leaf Page
    Provides a clean, single-step interface for entering unique product data.
--}}

<x-admin.modal id="{{ $modalId }}" title="{{ $selectedProductId ? 'Edit Product' : 'Add New Product' }}" maxWidth="3xl">
    
    <!-- Wizard Steps Content (Single Step Layout) -->
    <div class="p-xl overflow-y-auto max-h-[600px] space-y-xl" style="min-height: 400px;">

        <!-- Row 1: Title and Stock on same row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-md items-end">
            <!-- Product Title -->
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Product Title *</label>
                <input type="text" wire:model="basicInfo.title" placeholder="e.g. Classic Premium Linen Shirt" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                @error('basicInfo.title') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Available Stock -->
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Available Stock <span class="text-on-surface-variant/60 font-normal">(leave empty for N/A)</span></label>
                <div class="flex items-center gap-sm">
                    <input type="number" wire:model.live="nonVariantStock" placeholder="N/A" min="0" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @if($nonVariantStock !== '')
                        <span class="font-label-md text-on-surface font-bold text-xs whitespace-nowrap">{{ number_format((int)$nonVariantStock) }} units</span>
                    @else
                        <span class="flex items-center gap-xs text-[10px] text-on-surface-variant/80 bg-surface-container px-xs py-2 rounded-md border border-outline-variant/30 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[12px]">all_inclusive</span>
                            Unlimited
                        </span>
                    @endif
                </div>
                @error('nonVariantStock') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Row 2: Media upload, full width -->
        <div class="space-y-md border-t border-outline-variant/10 pt-lg">
            <label class="font-label-md text-on-surface-variant select-none">Product Media</label>
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
            
            <div x-data="{ dragging: false }"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; $wire.upload('mediaUploads', $event.dataTransfer.files)"
                 class="border border-outline-variant/60 rounded-xl p-lg flex flex-col items-center justify-center text-center transition-all bg-surface-container-low/20 select-none relative w-full"
                 :class="dragging ? 'border-primary bg-primary/5' : 'hover:bg-surface-container-low/30'">
                <input type="file" multiple id="media-uploader-wiz-cat" class="hidden" wire:model="mediaUploads" accept="image/png, image/jpeg, image/jpg, image/webp">
                <label for="media-uploader-wiz-cat" class="cursor-pointer flex flex-col items-center">
                    <span class="material-symbols-outlined text-[36px] text-on-surface-variant/70 mb-xs select-none">cloud_upload</span>
                    <span class="font-bold text-on-surface text-base mb-xxs">Add Product Images</span>
                    <span class="px-md py-sm bg-[#5c44c4] hover:bg-[#4d37a8] text-white rounded-lg font-semibold text-xs transition-all shadow-sm select-none mt-xs">Browse Images</span>
                </label>
            </div>

            <!-- Loader for Image Upload -->
            <div wire:loading wire:target="mediaUploads" class="w-full bg-surface-container-low/40 border border-outline-variant/20 rounded-xl p-md flex items-center justify-center gap-sm">
                <svg class="animate-spin h-5 w-5 text-[#5c44c4]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-bold text-primary animate-pulse">Uploading...</span>
            </div>

            <!-- Gallery Grid -->
            @if(!empty($existingMedia) || !empty($mediaUploads))
                <div class="space-y-md mt-md">
                    @if(!empty($existingMedia))
                        <div wire:ignore.self x-data
                             x-init="$nextTick(() => {
                                 const grid = $el.querySelector('.sortable-grid-wiz-cat');
                                 if (grid && typeof Sortable !== 'undefined') {
                                     new Sortable(grid, {
                                         animation: 250, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
                                         filter: '.no-drag', preventOnFilter: false,
                                         onEnd: function(evt) { if (evt.oldIndex !== evt.newIndex) { $wire.call('reorderExistingMediaInArray', evt.oldIndex, evt.newIndex); } }
                                     });
                                 }
                             })">
                            <div class="sortable-grid-wiz-cat grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-md select-none">
                                @foreach($existingMedia as $index => $m)
                                    <div class="drag-handle border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 cursor-grab active:cursor-grabbing transition-all duration-200 shadow-sm hover:scale-[1.02]"
                                         wire:key="existing-media-wiz-cat-{{ $m['id'] }}">
                                        <img src="{{ Storage::url($m['file_path']) }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false">
                                        <button type="button" x-on:click.stop.prevent="$wire.call('removeExistingMedia', {{ $m['id'] }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-6 h-6 flex items-center justify-center absolute top-1 right-1 shadow z-10 transition-colors cursor-pointer" title="Delete">
                                            <span class="material-symbols-outlined text-[14px] font-bold">close</span>
                                        </button>
                                        @if($m['is_primary'])
                                            <span class="absolute bottom-1 left-1 px-xs py-0.5 bg-[#5c44c4] text-on-primary text-[8px] font-bold rounded shadow-sm select-none pointer-events-none">COVER</span>
                                        @else
                                            <button type="button" x-on:click.stop.prevent="$wire.call('setPrimaryMedia', {{ $m['id'] }})" class="absolute bottom-1 left-1 px-xs py-0.5 bg-white/95 text-[#5c44c4] text-[8px] font-bold rounded shadow-sm select-none opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white cursor-pointer">SET COVER</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($mediaUploads))
                        <div wire:ignore.self x-data
                             x-init="$nextTick(() => {
                                 const grid = $el.querySelector('.sortable-uploads-wiz-cat');
                                 if (grid && typeof Sortable !== 'undefined') {
                                     new Sortable(grid, {
                                         animation: 250, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
                                         filter: '.no-drag', preventOnFilter: false,
                                         onEnd: function(evt) { if (evt.oldIndex !== evt.newIndex) { $wire.call('reorderUploadedMedia', evt.oldIndex, evt.newIndex); } }
                                     });
                                 }
                             })">
                            <div class="sortable-uploads-wiz-cat grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-md select-none">
                                @foreach($mediaUploads as $index => $file)
                                    <div class="border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200 hover:scale-[1.02]" wire:key="new-upload-wiz-cat-{{ $index }}">
                                        @php $tempUrl = null; try { $tempUrl = $file->temporaryUrl(); } catch (\Exception $e) {} @endphp
                                        @if($tempUrl)
                                            <img src="{{ $tempUrl }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false" />
                                        @else
                                            <div class="flex flex-col items-center justify-center text-on-surface-variant/50">
                                                <span class="material-symbols-outlined text-xl">image</span>
                                            </div>
                                        @endif
                                        <button type="button" x-on:click.stop.prevent="$wire.call('deleteUploadedFile', {{ $index }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-6 h-6 flex items-center justify-center absolute top-1 right-1 shadow z-10 transition-colors cursor-pointer" title="Remove">
                                            <span class="material-symbols-outlined text-[14px] font-bold">close</span>
                                        </button>
                                        <span class="absolute bottom-1 left-1 px-xs py-0.5 bg-secondary text-on-secondary text-[8px] font-bold rounded shadow-sm select-none">NEW</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Row 3: Product Tags -->
        <div class="space-y-sm border-t border-outline-variant/10 pt-lg select-none">
            <label class="font-label-md text-on-surface-variant font-semibold">Product Tags</label>
            <div class="flex flex-wrap gap-xs">
                @forelse($availableTags as $tag)
                    @php $isSel = in_array($tag->id, $selectedTagIds); @endphp
                    <button type="button" wire:click="toggleTag({{ $tag->id }})"
                            class="px-md py-xs rounded-full text-xs font-semibold border transition-all flex items-center gap-xxs cursor-pointer
                            {{ $isSel 
                                ? 'bg-primary-container text-on-primary border-primary hover:bg-primary-container/90' 
                                : 'bg-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low' }}">
                        @if($isSel)
                            <span class="material-symbols-outlined text-[14px]">check</span>
                        @else
                            <span class="material-symbols-outlined text-[14px] text-outline-variant">add</span>
                        @endif
                        <span>{{ $tag->name }}</span>
                    </button>
                @empty
                    <p class="text-xs text-on-surface-variant italic">No tags created yet. Go to Tags Management to add tags.</p>
                @endforelse
            </div>
        </div>

        <!-- Row 3.5: Product Units -->
        <div class="space-y-md border-t border-outline-variant/10 pt-lg">
            <div class="flex justify-between items-center select-none">
                <label class="font-label-md text-on-surface-variant font-semibold">Product Units</label>
                @php
                    $category = \App\Models\Category::find($currentCategoryId);
                    $catDefaults = $category ? $category->default_product_config : null;
                    $catUnits = $catDefaults['units'] ?? null;
                @endphp
                @if($catUnits)
                    <div class="flex items-center gap-xs text-[10px] font-bold text-secondary bg-secondary-container/10 border border-secondary/20 rounded-lg px-2.5 py-1">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                        <span>
                            Category Default:
                            @if(!empty($catUnits['level2_name']) && !empty($catUnits['level2_conversion']))
                                1 {{ $catUnits['level2_name'] }} = {{ (int)$catUnits['level2_conversion'] }} {{ $catUnits['level1_name'] }}s
                            @else
                                {{ $catUnits['level1_name'] }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-md items-stretch">
                <!-- Level 1 -->
                <div class="flex-1 bg-surface-container-low/60 border border-outline-variant/30 rounded-xl p-md space-y-md">
                    <div class="flex items-center gap-xs select-none">
                        <span class="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] font-bold flex items-center justify-center">1</span>
                        <span class="font-label-md text-primary font-bold">Level 1 — Base Unit <span class="text-error">*</span></span>
                    </div>
                    <div class="grid grid-cols-2 gap-sm">
                        <div class="space-y-xs">
                            <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                            <input type="text" wire:model.live="units.level1_name" placeholder="Piece" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm">
                            @error('units.level1_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-xs">
                            <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                            <input type="text" wire:model.live="units.level1_code" placeholder="pcs" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                            @error('units.level1_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Level 2 -->
                <div class="flex-1 bg-surface-container-low/40 border border-outline-variant/30 rounded-xl p-md space-y-md">
                    <div class="flex items-center gap-xs select-none">
                        <span class="w-5 h-5 rounded-full {{ !empty($units['level2_name']) ? 'bg-secondary text-on-secondary' : 'bg-outline-variant/40 text-on-surface-variant' }} text-[11px] font-bold flex items-center justify-center">2</span>
                        <span class="font-label-md {{ !empty($units['level2_name']) ? 'text-secondary font-bold' : 'text-on-surface-variant/60 font-semibold' }}">Level 2 — Group Unit <span class="text-on-surface-variant/50 font-normal text-xs">(optional)</span></span>
                    </div>
                    <div class="grid grid-cols-2 gap-sm">
                        <div class="space-y-xs">
                            <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                            <input type="text" wire:model.live="units.level2_name" placeholder="Box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm">
                            @error('units.level2_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-xs">
                            <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                            <input type="text" wire:model.live="units.level2_code" placeholder="box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                            @error('units.level2_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="space-y-xs">
                        <label class="text-xs text-on-surface-variant font-medium">Relation: How many <strong>{{ $units['level1_name'] ?: 'base units' }}</strong> in 1 <strong>{{ $units['level2_name'] ?: 'group unit' }}</strong>?</label>
                        <div class="flex items-center gap-sm">
                            <div class="flex items-center gap-xs bg-white border border-outline-variant/50 rounded-lg px-sm py-xs focus-within:ring-2 focus-within:ring-secondary w-36">
                                <span class="text-xs text-on-surface-variant select-none font-medium whitespace-nowrap">1 {{ $units['level2_name'] ?: '...' }} =</span>
                                <input type="number" wire:model.live="units.level2_conversion" placeholder="qty" min="0.0001" step="any" class="w-16 bg-transparent border-none focus:ring-0 outline-none text-on-surface font-bold text-sm text-right">
                            </div>
                            <span class="text-sm font-bold text-on-surface-variant">{{ $units['level1_name'] ?: '...' }}</span>
                        </div>
                        @error('units.level2_conversion') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Description input, full width -->
        <div class="space-y-xs border-t border-outline-variant/10 pt-lg">
            <label class="font-label-md text-on-surface-variant select-none font-semibold">Description</label>
            <div class="border border-outline-variant/60 rounded-lg overflow-hidden bg-white shadow-sm">
                <!-- Toolbar -->
                <div class="px-md py-xs border-b border-outline-variant/30 bg-surface-container-low/40 flex items-center gap-md select-none flex-wrap">
                    <div class="flex items-center gap-xs">
                        <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz-cat'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '**' + text.substring(start, end) + '**' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-extrabold text-sm text-primary" title="Bold">B</button>
                        <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz-cat'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '*' + text.substring(start, end) + '*' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container italic font-bold text-sm text-primary" title="Italic">I</button>
                        <button type="button" onclick="const ta = document.getElementById('desc-editor-wiz-cat'); const start = ta.selectionStart; text = ta.value; ta.value = text.substring(0, start) + '### ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-bold text-sm text-primary" title="Heading">H</button>
                    </div>
                    <div class="w-px h-5 bg-outline-variant/40"></div>
                    <div class="flex items-center gap-xs">
                        <button type="button" wire:click="$toggle('isPreviewMode')" class="w-8 h-8 rounded flex items-center justify-center {{ $isPreviewMode ? 'bg-secondary/15 text-secondary' : 'text-primary hover:bg-surface-container' }}" title="Toggle Preview">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </button>
                    </div>
                </div>

                @if(!$isPreviewMode)
                    <textarea id="desc-editor-wiz-cat" rows="9" wire:model="basicInfo.description" placeholder="Enter product description..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[240px]"></textarea>
                @else
                    <div class="prose max-w-none p-md min-h-[240px] bg-surface-container-low/20 text-on-surface text-sm overflow-y-auto">
                        {!! Illuminate\Support\Str::markdown($basicInfo['description'] ?? '*Enter product description...*') !!}
                    </div>
                @endif
            </div>
            @error('basicInfo.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
        </div>

    </div>

    <x-slot name="footer">
        <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
        <x-admin.button variant="primary" icon="save" wire:click="save">Save Product</x-admin.button>
    </x-slot>
</x-admin.modal>
