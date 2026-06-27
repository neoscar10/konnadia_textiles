<div>
    @php
        $renderCategorySelectTree = function($categories, $wireModelPath, $depth = 0) use (&$renderCategorySelectTree) {
            foreach ($categories as $cat) {
                $hasChildren = $cat->children->isNotEmpty();
                @endphp
                <div x-data="{ expanded: false }" 
                     x-show="search === '' || '{{ strtolower(addslashes($cat->name)) }}'.includes(search.toLowerCase())"
                     class="space-y-1">
                    <div style="padding-left: {{ $depth * 1 }}rem" class="flex items-center gap-1 hover:bg-slate-50 rounded-lg p-1">
                        @if($hasChildren)
                            <button type="button" @click.stop="expanded = !expanded" class="p-1 hover:bg-slate-200 rounded flex items-center justify-center focus:outline-none text-slate-500">
                                <span class="material-symbols-outlined text-[16px] transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">chevron_right</span>
                            </button>
                        @else
                            <div class="w-6"></div>
                        @endif
                        <button type="button" 
                                wire:click="$set('{{ $wireModelPath }}', '{{ $cat->id }}')"
                                @click="open = false; selectedName = '{{ addslashes($cat->name) }}'"
                                class="text-left text-xs text-slate-700 hover:text-primary font-medium flex-1 truncate py-0.5">
                            {{ $cat->name }}
                            @if($depth > 0)
                                <span class="text-[9px] text-slate-400 font-normal ml-1">Subcategory</span>
                            @endif
                        </button>
                    </div>
                    @if($hasChildren)
                        <div x-show="expanded || search !== ''" class="space-y-1">
                            @php
                            $renderCategorySelectTree($cat->children, $wireModelPath, $depth + 1);
                            @endphp
                        </div>
                    @endif
                </div>
                @php
            }
        };
    @endphp
    <x-slot:title>Home Content CMS</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Homepage Content Builder</h1>
            <p class="font-body-md text-on-surface-variant">Design and arrange the dynamic promo blocks and sliders for the customer portal.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button wire:click="createSection" variant="primary" icon="add">Add Content Block</x-admin.button>
        </div>
    </div>

    <!-- Tabs & Filter Bar -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md</x-slot:bodyClass>
        
        <div class="flex border-b border-outline-variant/30 w-full sm:w-auto">
            <button wire:click="$set('activeTab', 'all')" class="px-lg py-sm font-label-md transition-all border-b-2 {{ $activeTab === 'all' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                All Sections
            </button>
            <button wire:click="$set('activeTab', 'active')" class="px-lg py-sm font-label-md transition-all border-b-2 {{ $activeTab === 'active' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                Active Only
            </button>
            <button wire:click="$set('activeTab', 'inactive')" class="px-lg py-sm font-label-md transition-all border-b-2 {{ $activeTab === 'inactive' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                Inactive Only
            </button>
        </div>

        <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full sm:max-w-xs transition-all">
            <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none">search</span>
            <input type="text" wire:model.live.debounce.300ms="searchSections" placeholder="Search blocks..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
        </div>
    </x-admin.card>

    <!-- Sections Table with SortableJS -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-x-auto pb-32" x-data="{
            initSortable() {
                if (typeof Sortable === 'undefined') return;
                Sortable.create(this.$refs.sortableTable, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: (evt) => {
                        let rows = this.$refs.sortableTable.querySelectorAll('tr[data-id]');
                        let orderedIds = Array.from(rows).map(row => row.getAttribute('data-id'));
                        @this.call('updateOrder', orderedIds);
                    }
                });
            }
        }" x-init="initSortable()">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="w-10 px-lg py-md"></th>
                        <th class="px-lg py-md">Section Title</th>
                        <th class="px-lg py-md">Block Type</th>
                        <th class="px-lg py-md text-center">Items</th>
                        <th class="px-lg py-md">Schedule Range</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableTable" class="divide-y divide-outline-variant/10">
                    @forelse($sections as $sec)
                    <tr data-id="{{ $sec->id }}" class="hover:bg-primary/[0.01] transition-colors group">
                        <td class="px-lg py-lg text-center select-none">
                            <span class="material-symbols-outlined text-on-surface-variant/60 cursor-grab active:cursor-grabbing drag-handle text-[20px] select-none">drag_indicator</span>
                        </td>
                        <td class="px-lg py-lg">
                            <div class="font-bold text-primary">{{ $sec->title ?: 'Untitled Block' }}</div>
                            @if($sec->subtitle)
                                <div class="text-xs text-on-surface-variant/70 mt-0.5 truncate max-w-xs">{{ $sec->subtitle }}</div>
                            @endif
                        </td>
                        <td class="px-lg py-lg whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-surface-container-high border border-outline-variant/30 text-[#001229]">
                                {{ str_replace('_', ' ', $sec->type) }}
                            </span>
                        </td>
                        <td class="px-lg py-lg text-center font-bold text-on-surface-variant">{{ $sec->items_count }}</td>
                        <td class="px-lg py-lg text-xs text-slate-500 whitespace-nowrap">
                            @if($sec->starts_at || $sec->ends_at)
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm select-none">calendar_today</span>
                                    <span>
                                        {{ $sec->starts_at ? $sec->starts_at->format('jS M Y') : 'Always' }} - 
                                        {{ $sec->ends_at ? $sec->ends_at->format('jS M Y') : 'Always' }}
                                    </span>
                                </div>
                            @else
                                <span class="text-slate-400">Always Visible</span>
                            @endif
                        </td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <x-admin.badge type="{{ $sec->is_active ? 'success' : 'default' }}">
                                {{ $sec->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-right whitespace-nowrap">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="editSection({{ $sec->id }})" icon="edit" label="Edit Block" />
                                <x-admin.action-menu-item wire:click="toggleStatus({{ $sec->id }})" icon="{{ $sec->is_active ? 'block' : 'check_circle' }}" label="{{ $sec->is_active ? 'Deactivate' : 'Activate' }}" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $sec->id }})" icon="delete" label="Delete Section" class="text-error hover:text-error hover:bg-error/10" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-2xl text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-sm text-outline">dashboard_customize</span>
                                <p class="font-body-lg">No content blocks configured yet.</p>
                                <p class="text-sm">Click "Add Content Block" above to design promotional sliders and hero banners.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sections->hasPages() || $sections->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $sections->firstItem() ?? 0 }} to {{ $sections->lastItem() ?? 0 }} of {{ $sections->total() }} sections</span>
            <div class="flex items-center gap-xs">
                {{ $sections->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- Multi-Step Modal Wizard -->
    <x-admin.modal id="home-content-wizard" title="{{ $editingSectionId ? 'Edit Content Block' : 'Add Content Block' }}" maxWidth="5xl">
        <div class="flex flex-col min-h-[60vh] max-h-[80vh]">
            
            <!-- Step Indicators -->
            <div class="flex items-center justify-between border-b border-outline-variant/30 pb-md mb-lg text-xs font-semibold text-slate-500">
                <button type="button" @if($editingSectionId) wire:click="$set('wizardStep', 1)" @endif class="flex items-center gap-2 focus:outline-none {{ $editingSectionId ? 'cursor-pointer hover:text-primary' : 'cursor-default' }} {{ $wizardStep === 1 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 1 ? 'bg-primary text-white border-primary' : '' }}">1</span>
                    <span>Choose Type</span>
                </button>
                <div class="w-12 h-px bg-slate-200"></div>
                <button type="button" @if($editingSectionId) wire:click="$set('wizardStep', 2)" @endif class="flex items-center gap-2 focus:outline-none {{ $editingSectionId ? 'cursor-pointer hover:text-primary' : 'cursor-default' }} {{ $wizardStep === 2 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 2 ? 'bg-primary text-white border-primary' : '' }}">2</span>
                    <span>Configure Layout</span>
                </button>
                <div class="w-12 h-px bg-slate-200"></div>
                <button type="button" @if($editingSectionId) wire:click="$set('wizardStep', 3)" @endif class="flex items-center gap-2 focus:outline-none {{ $editingSectionId ? 'cursor-pointer hover:text-primary' : 'cursor-default' }} {{ $wizardStep === 3 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 3 ? 'bg-primary text-white border-primary' : '' }}">3</span>
                    <span>Add/Select Items</span>
                </button>
                <div class="w-12 h-px bg-slate-200"></div>
                <button type="button" @if($editingSectionId) wire:click="$set('wizardStep', 4)" @endif class="flex items-center gap-2 focus:outline-none {{ $editingSectionId ? 'cursor-pointer hover:text-primary' : 'cursor-default' }} {{ $wizardStep === 4 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 4 ? 'bg-primary text-white border-primary' : '' }}">4</span>
                    <span>Live Preview</span>
                </button>
            </div>

            <!-- Steps Scroll Container -->
            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                
                <!-- STEP 1: Choose Type -->
                @if($wizardStep === 1)
                    <div class="space-y-md">
                        <h4 class="font-title-lg text-[#001229] mb-md">Select Homepage Content Block Type</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            
                            <!-- Banner -->
                            <button type="button" wire:click="$set('sectionType', 'banner')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'banner' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">photo_library</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Full Width Hero Banner</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">A single full-width image banner. Ideal for major promotions. Supports direct image-click target or optional CTA button overlay.</p>
                                </div>
                            </button>

                            <!-- Banner Slider -->
                            <button type="button" wire:click="$set('sectionType', 'banner_slider')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'banner_slider' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">view_carousel</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Full Width Banner Slider</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Cycle through multiple full-width banners in a beautiful carousel. Great for highlighting multiple promotions. Optional CTA overlays.</p>
                                </div>
                            </button>

                            <!-- Image/Text Card -->
                            <button type="button" wire:click="$set('sectionType', 'image_text_card')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'image_text_card' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">chrome_reader_mode</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Image / Text Card</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">A split block showing an image on one side and a rich formatted description (Markdown editor) on the other. Responsive stacked layout.</p>
                                </div>
                            </button>

                            <!-- Category Slider -->
                            <button type="button" wire:click="$set('sectionType', 'category_slider')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'category_slider' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">widgets</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Category Quick Slider</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">A horizontal carousel of your product categories. Promotes division discovery by letting buyers browse straight to categories.</p>
                                </div>
                            </button>

                            <!-- Product Slider -->
                            <button type="button" wire:click="$set('sectionType', 'product_slider')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'product_slider' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">shopping_bag</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Static Product Slider</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Curate featured products manually. Display bestsellers, special deals, or new arrivals with dynamic level-specific pricing.</p>
                                </div>
                            </button>

                            <!-- Image Slider -->
                            <button type="button" wire:click="$set('sectionType', 'image_slider')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'image_slider' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">auto_awesome_motion</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Image Slide Carousel</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">An interactive image slider gallery. Upload multiple cover cards, configure individual CTAs, and redirect links for campaign promotions.</p>
                                </div>
                            </button>

                        </div>
                    </div>
                @endif

                <!-- STEP 2: Configure Layout -->
                @if($wizardStep === 2)
                    <div class="space-y-lg">
                        <h4 class="font-title-lg text-[#001229]">Block Configuration & Details</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                            <div>
                                <label class="text-xs text-slate-500 font-bold block mb-1.5">Section Title</label>
                                <input type="text" wire:model="sectionTitle" placeholder="e.g. Featured Wholesale Deals" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 font-bold block mb-1.5">Section Subtitle</label>
                                <input type="text" wire:model="sectionSubtitle" placeholder="e.g. Exclusive B2B discounts on catalog staples" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                            </div>
                        </div>

                        <!-- Schedule settings -->
                        <div class="p-lg bg-slate-50 border border-outline-variant/10 rounded-xl space-y-md">
                            <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Visibility & Schedule Settings</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-md items-end">
                                <div class="sm:col-span-4">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Publish Method</label>
                                    <select wire:model.live="visibilityMode" class="w-full text-xs bg-white border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                        <option value="live">Go Live Now</option>
                                        <option value="schedule">Schedule visibility</option>
                                    </select>
                                </div>
                                @if($visibilityMode === 'schedule')
                                    <div class="sm:col-span-3">
                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Starts At (Optional)</label>
                                        <input type="datetime-local" wire:model="sectionStartsAt" class="w-full text-xs bg-white border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Ends At (Optional)</label>
                                        <input type="datetime-local" wire:model="sectionEndsAt" class="w-full text-xs bg-white border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div class="sm:col-span-2">
                                @else
                                    <div class="sm:col-span-8">
                                @endif
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Publish Status</label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model.live="sectionIsActive" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                            <span class="ml-2 text-xs font-bold text-slate-700">Active</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sliders Display Settings -->
                        @if($sectionType !== 'banner' && $sectionType !== 'banner_slider' && $sectionType !== 'image_text_card')
                            <div>
                                <label class="text-xs text-slate-500 font-bold block mb-1.5">Display Limit</label>
                                <input type="number" wire:model="sectionDisplayLimit" min="1" max="50" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold" placeholder="e.g. 10">
                                <p class="text-[10px] text-slate-400 mt-1">Specify the maximum number of items (categories/products/slides) to render in this horizontal slider.</p>
                            </div>
                        @endif

                    </div>
                @endif

                <!-- STEP 3: Add/Select Items -->
                @if($wizardStep === 3)
                    <div class="space-y-lg">
                        <h4 class="font-title-lg text-[#001229] mb-md">Populate Block Content</h4>

                        <!-- BANNER FORM -->
                        @if($sectionType === 'banner')
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-lg">
                                <div class="md:col-span-4 space-y-md">
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Banner Image Cover (Ratio 16:5 / 16:6)*</label>
                                        <div class="relative flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-4 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer group min-h-[120px]">
                                            <input type="file" wire:model="bannerImage" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                            <div class="text-center space-y-1 z-0">
                                                <span class="material-symbols-outlined text-slate-400 text-3xl group-hover:text-primary transition-colors select-none">cloud_upload</span>
                                                <p class="text-xs font-bold text-slate-600 group-hover:text-primary transition-colors">Choose Banner Image</p>
                                                <p class="text-[10px] text-slate-400">JPEG, PNG, WEBP (Max 5MB)</p>
                                            </div>
                                            <!-- Loading indicator overlay -->
                                            <div wire:loading wire:target="bannerImage" class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center rounded-xl z-20 space-y-2">
                                                <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                                <span class="text-xs font-bold text-primary">Uploading...</span>
                                            </div>
                                        </div>
                                        @error('bannerImage')
                                            <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                        @enderror

                                        @if($bannerImage)
                                            <div class="mt-3 relative rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs">
                                                <img src="{{ $bannerImage->temporaryUrl() }}" class="h-24 w-full object-cover">
                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-1 text-[10px] text-white font-bold truncate">
                                                    {{ $bannerImage->getClientOriginalName() }}
                                                </div>
                                            </div>
                                        @elseif($bannerExistingImage)
                                            <div class="mt-3 relative rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs">
                                                <img src="{{ asset('storage/' . $bannerExistingImage) }}" class="h-24 w-full object-cover">
                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-1 text-[10px] text-white font-bold">
                                                    Current Image
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="md:col-span-8 space-y-md pl-md">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">Link Destination Type</label>
                                            <select wire:model.live="bannerLinkType" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                <option value="none">No Link (Unclickable)</option>
                                                <option value="category">Linked Category</option>
                                                <option value="product">Linked Product</option>
                                                <option value="url">External Redirect URL</option>
                                            </select>
                                        </div>
                                        @if($bannerLinkType !== 'none')
                                            <div>
                                                <label class="text-xs text-slate-500 font-bold block mb-1.5">CTA Button Label (Optional)</label>
                                                <input type="text" wire:model="bannerCtaLabel" placeholder="e.g. Shop Now (Leave blank to make whole banner clickable)" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                            </div>
                                        @endif
                                    </div>

                                    @if($bannerLinkType === 'category')
                                        <div>
                                            <label class="text-xs text-[#001229] font-bold block mb-1.5">Select Linked Category</label>
                                            @php
                                                $selectedCategory = $bannerLinkCategoryId === 'all' || $bannerLinkCategoryId == '0' || $bannerLinkCategoryId == ''
                                                    ? (object)['name' => 'All Categories (Main Catalog)'] 
                                                    : collect($categoriesList)->firstWhere('id', $bannerLinkCategoryId);
                                                $selectedCategoryName = $selectedCategory ? $selectedCategory->name : 'Select Category';
                                            @endphp
                                            <div x-data="{ open: false, search: '', selectedName: '{{ addslashes($selectedCategoryName) }}' }" class="relative">
                                                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full flex items-center justify-between text-left text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                    <span x-text="selectedName"></span>
                                                    <span class="material-symbols-outlined text-slate-400 text-[18px] select-none">arrow_drop_down</span>
                                                </button>
                                                
                                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-outline-variant/30 rounded-lg shadow-lg p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                                    <!-- Search field -->
                                                    <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 border border-outline-variant/20 rounded-md mb-2">
                                                        <span class="material-symbols-outlined text-slate-400 text-[16px] select-none">search</span>
                                                        <input type="text" x-model="search" placeholder="Search category name..." class="w-full bg-transparent border-0 p-0 text-xs focus:ring-0 outline-none h-6">
                                                    </div>
                                                    
                                                    <!-- Category list -->
                                                    <div class="space-y-1">
                                                        <div class="flex items-center gap-1 hover:bg-slate-50 rounded-lg p-1">
                                                            <div class="w-6"></div>
                                                            <button type="button" 
                                                                    wire:click="$set('bannerLinkCategoryId', 'all')"
                                                                    @click="open = false; selectedName = 'All Categories (Main Catalog)'"
                                                                    class="text-left text-xs text-slate-700 hover:text-primary font-bold flex-1 py-0.5">
                                                                All Categories (Main Catalog)
                                                            </button>
                                                        </div>
                                                        @php
                                                            $rootCategoriesSelect = \App\Models\Category::whereNull('parent_id')->with('children.children')->orderBy('sort_order')->orderBy('name')->get();
                                                            $renderCategorySelectTree($rootCategoriesSelect, 'bannerLinkCategoryId');
                                                        @endphp
                                                    </div>
                                                </div>
                                            </div>
                                            @error('bannerLinkCategoryId') <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($bannerLinkType === 'product')
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">Select Linked Product</label>
                                            <select wire:model="bannerLinkProductId" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                <option value="">Select Product</option>
                                                @php $allProds = \App\Models\Product::where('is_active', true)->orderBy('title')->get(); @endphp
                                                @foreach($allProds as $prod)
                                                    <option value="{{ $prod->id }}">{{ $prod->title }} (SKU: {{ $prod->sku }})</option>
                                                @endforeach
                                            </select>
                                            @error('bannerLinkProductId') <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($bannerLinkType === 'url')
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">External URL (Open in a new tab)</label>
                                            <input type="text" wire:model="bannerExternalUrl" placeholder="https://example.com" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                            @error('bannerExternalUrl') <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- BANNER SLIDER FORM -->
                        @if($sectionType === 'banner_slider')
                            <div class="space-y-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs text-slate-400 font-semibold uppercase select-none block">Banners List</span>
                                    <x-admin.button type="button" wire:click="addSlide" variant="outline" icon="add" class="text-xs">Add Banner Slide</x-admin.button>
                                </div>

                                <div class="space-y-4 max-h-[380px] overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($slides as $index => $slide)
                                        <div class="p-lg bg-white border border-outline-variant/20 rounded-xl shadow-xs relative space-y-4">
                                            <div class="flex justify-between items-center bg-slate-50 border border-outline-variant/10 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600">
                                                <span>Banner #{{ $index + 1 }}</span>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button" wire:click="moveSlide({{ $index }}, 'up')" @if($index === 0) disabled class="opacity-30 cursor-not-allowed" @endif class="text-slate-600 hover:text-[#001229]" title="Move Up">
                                                        <span class="material-symbols-outlined text-sm font-bold">arrow_upward</span>
                                                    </button>
                                                    <button type="button" wire:click="moveSlide({{ $index }}, 'down')" @if($index === count($slides) - 1) disabled class="opacity-30 cursor-not-allowed" @endif class="text-slate-600 hover:text-[#001229]" title="Move Down">
                                                        <span class="material-symbols-outlined text-sm font-bold">arrow_downward</span>
                                                    </button>
                                                    <button type="button" wire:click="removeSlide({{ $index }})" class="text-rose-500 hover:text-rose-700" title="Delete Slide">
                                                        <span class="material-symbols-outlined text-sm font-bold">delete</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-md">
                                                <div class="md:col-span-4 space-y-md">
                                                    <div>
                                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Banner Image*</label>
                                                        <div class="relative flex flex-col items-center justify-center border border-dashed border-slate-200 rounded-lg p-3 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer group min-h-[90px]">
                                                            <input type="file" wire:model="slides.{{ $index }}.upload" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                                            <div class="text-center space-y-1 z-0">
                                                                <span class="material-symbols-outlined text-slate-400 text-2xl group-hover:text-primary transition-colors select-none">cloud_upload</span>
                                                                <p class="text-[10px] font-bold text-slate-600 group-hover:text-primary transition-colors">Choose Image File</p>
                                                            </div>
                                                            <!-- Loading indicator overlay -->
                                                            <div wire:loading wire:target="slides.{{ $index }}.upload" class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center rounded-lg z-20 space-y-1">
                                                                <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                                                <span class="text-[9px] font-bold text-primary">Uploading...</span>
                                                            </div>
                                                        </div>
                                                        @error("slides.{$index}.upload") <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                                        
                                                        @if(isset($slide['upload']))
                                                            <div class="mt-2 relative rounded overflow-hidden border border-outline-variant/30 shadow-xs">
                                                                <img src="{{ $slide['upload']->temporaryUrl() }}" class="h-12 w-full object-cover">
                                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-0.5 text-[9px] text-white font-bold truncate">
                                                                    {{ $slide['upload']->getClientOriginalName() }}
                                                                </div>
                                                            </div>
                                                        @elseif(isset($slide['existing_image']))
                                                            <div class="mt-2 relative rounded overflow-hidden border border-outline-variant/30 shadow-xs">
                                                                <img src="{{ asset('storage/' . $slide['existing_image']) }}" class="h-12 w-full object-cover">
                                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-0.5 text-[9px] text-white font-bold">
                                                                    Current Image
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="md:col-span-8 space-y-md pl-md">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Link Type</label>
                                                            <select wire:model.live="slides.{{ $index }}.link_type" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                <option value="none">No Link (Unclickable)</option>
                                                                <option value="category">Linked Category</option>
                                                                <option value="product">Linked Product</option>
                                                                <option value="url">External Redirect URL</option>
                                                            </select>
                                                        </div>
                                                        @if(($slide['link_type'] ?? 'none') !== 'none')
                                                            <div>
                                                                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">CTA Button Label (Optional)</label>
                                                                <input type="text" wire:model="slides.{{ $index }}.cta_label" placeholder="e.g. Shop Now" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if(($slide['link_type'] ?? 'none') === 'category')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Select Linked Category</label>
                                                            @php
                                                                $selectedSlideCatId = $slide['link_category_id'] ?? '';
                                                                $selectedSlideCat = $selectedSlideCatId === 'all' || $selectedSlideCatId == '0' || $selectedSlideCatId == ''
                                                                    ? (object)['name' => 'All Categories (Main Catalog)'] 
                                                                    : collect($categoriesList)->firstWhere('id', $selectedSlideCatId);
                                                                $selectedSlideCatName = $selectedSlideCat ? $selectedSlideCat->name : 'Select Category';
                                                            @endphp
                                                            <div x-data="{ open: false, search: '', selectedName: '{{ addslashes($selectedSlideCatName) }}' }" class="relative">
                                                                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full flex items-center justify-between text-left text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                    <span x-text="selectedName"></span>
                                                                    <span class="material-symbols-outlined text-slate-400 text-[18px] select-none">arrow_drop_down</span>
                                                                </button>
                                                                
                                                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-outline-variant/30 rounded-lg shadow-lg p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                                                    <!-- Search field -->
                                                                    <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 border border-outline-variant/20 rounded-md mb-2">
                                                                        <span class="material-symbols-outlined text-slate-400 text-[16px] select-none">search</span>
                                                                        <input type="text" x-model="search" placeholder="Search category name..." class="w-full bg-transparent border-0 p-0 text-xs focus:ring-0 outline-none h-6">
                                                                    </div>
                                                                    
                                                                    <!-- Category list -->
                                                                    <div class="space-y-1">
                                                                        <div class="flex items-center gap-1 hover:bg-slate-50 rounded-lg p-1">
                                                                            <div class="w-6"></div>
                                                                            <button type="button" 
                                                                                    wire:click="$set('slides.{{ $index }}.link_category_id', 'all')"
                                                                                    @click="open = false; selectedName = 'All Categories (Main Catalog)'"
                                                                                    class="text-left text-xs text-slate-700 hover:text-primary font-bold flex-1 py-0.5">
                                                                                All Categories (Main Catalog)
                                                                            </button>
                                                                        </div>
                                                                        @php
                                                                            $rootCategoriesSelectSlider = \App\Models\Category::whereNull('parent_id')->with('children.children')->orderBy('sort_order')->orderBy('name')->get();
                                                                            $renderCategorySelectTree($rootCategoriesSelectSlider, 'slides.' . $index . '.link_category_id');
                                                                        @endphp
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif(($slide['link_type'] ?? 'none') === 'product')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Select Linked Product</label>
                                                            <select wire:model="slides.{{ $index }}.link_product_id" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                <option value="">Select Product</option>
                                                                @php $allProds = \App\Models\Product::where('is_active', true)->orderBy('title')->get(); @endphp
                                                                @foreach($allProds as $prod)
                                                                    <option value="{{ $prod->id }}">{{ $prod->title }} (SKU: {{ $prod->sku }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @elseif(($slide['link_type'] ?? 'none') === 'url')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">External URL</label>
                                                            <input type="text" wire:model="slides.{{ $index }}.external_url" placeholder="https://example.com" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if(empty($slides))
                                        <div class="py-12 border rounded-xl border-dashed text-center text-slate-400 text-xs">
                                            <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">view_carousel</span>
                                            <p>No banner slides added yet. Click "Add Banner Slide" to begin.</p>
                                        </div>
                                    @endif
                                </div>
                                @error('slides')
                                    <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <!-- IMAGE / TEXT CARD FORM -->
                        @if($sectionType === 'image_text_card')
                            <div class="space-y-lg">
                                <!-- Top Row: Image & Position Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg bg-slate-50/50 p-lg rounded-xl border border-outline-variant/20 shadow-xs">
                                    <div>
                                        <label class="text-xs text-[#001229] font-bold block mb-1.5">Card Image*</label>
                                        <div class="relative flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-4 bg-white hover:bg-slate-50 transition-all cursor-pointer group min-h-[100px]">
                                            <input type="file" wire:model="cardImage" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                            <div class="text-center space-y-1 z-0">
                                                <span class="material-symbols-outlined text-slate-400 text-2xl group-hover:text-primary transition-colors select-none">cloud_upload</span>
                                                <p class="text-xs font-bold text-slate-600 group-hover:text-primary transition-colors">Choose Card Image</p>
                                                <p class="text-[9px] text-slate-400">JPEG, PNG, WEBP (Max 15MB)</p>
                                            </div>
                                            <!-- Loading indicator overlay -->
                                            <div wire:loading wire:target="cardImage" class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center rounded-xl z-20 space-y-2">
                                                <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                                <span class="text-xs font-bold text-primary">Uploading...</span>
                                            </div>
                                        </div>
                                        @error('cardImage')
                                            <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col justify-between space-y-md">
                                        <div>
                                            <label class="text-xs text-[#001229] font-bold block mb-1.5">Image Position (Desktop Layout)</label>
                                            <select wire:model="cardAlignment" class="w-full text-xs bg-white border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                <option value="left">Image on Left, Text on Right</option>
                                                <option value="right">Text on Left, Image on Right</option>
                                            </select>
                                        </div>
                                        
                                        @if($cardImage)
                                            <div class="relative rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs h-20">
                                                <img src="{{ $cardImage->temporaryUrl() }}" class="h-full w-full object-cover">
                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-0.5 text-[9px] text-white font-bold truncate">
                                                    {{ $cardImage->getClientOriginalName() }}
                                                </div>
                                            </div>
                                        @elseif($cardExistingImage)
                                            <div class="relative rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs h-20">
                                                <img src="{{ asset('storage/' . $cardExistingImage) }}" class="h-full w-full object-cover">
                                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/60 backdrop-blur-xs px-2 py-0.5 text-[9px] text-white font-bold">
                                                    Current Image
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Bottom Row: Accurate Markdown Editor -->
                                <div class="space-y-xs">
                                    <label class="text-xs text-[#001229] font-bold block mb-1.5">Card Text Content (Markdown formatted)*</label>
                                    
                                    <div class="border border-outline-variant/60 rounded-xl overflow-hidden bg-white shadow-xs">
                                        <!-- Quick Markup Toolbar -->
                                        <div class="px-md py-xs border-b border-outline-variant/20 bg-slate-50 flex items-center justify-between select-none flex-wrap gap-sm">
                                            <div class="flex items-center gap-xs">
                                                <!-- Bold -->
                                                <button type="button" onclick="insertMarkdownCard('**', '**')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 font-extrabold text-sm text-slate-700" title="Bold">B</button>
                                                <!-- Italic -->
                                                <button type="button" onclick="insertMarkdownCard('*', '*')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 italic font-bold text-sm text-slate-700" title="Italic">I</button>
                                                <!-- Heading -->
                                                <button type="button" onclick="insertMarkdownCard('### ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 font-bold text-sm text-slate-700" title="Heading">H</button>
                                                <div class="w-px h-4 bg-slate-300"></div>
                                                <!-- Quote -->
                                                <button type="button" onclick="insertMarkdownCard('> ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 text-slate-700 font-bold" title="Quote">"</button>
                                                <!-- Bullet List -->
                                                <button type="button" onclick="insertMarkdownCard('- ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 text-slate-700" title="Bullet List">
                                                    <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
                                                </button>
                                                <!-- Numbered List -->
                                                <button type="button" onclick="insertMarkdownCard('1. ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 text-slate-700" title="Numbered List">
                                                    <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                                                </button>
                                                <!-- Link -->
                                                <button type="button" onclick="insertMarkdownCard('[', '](url)')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-slate-200/50 text-slate-700" title="Add Link">
                                                    <span class="material-symbols-outlined text-[18px]">link</span>
                                                </button>
                                            </div>

                                            <div class="flex items-center gap-xs">
                                                <!-- Write/Preview tabs -->
                                                <button type="button" wire:click="$set('cardPreviewMode', false)" class="px-3 py-1 text-xs font-bold rounded-lg border transition-colors {{ !$cardPreviewMode ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/30 text-[#001229] hover:bg-slate-50' }}">
                                                    Write
                                                </button>
                                                <button type="button" wire:click="$set('cardPreviewMode', true)" class="px-3 py-1 text-xs font-bold rounded-lg border transition-colors {{ $cardPreviewMode ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/30 text-[#001229] hover:bg-slate-50' }}">
                                                    Preview
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Editor Area -->
                                        @if(!$cardPreviewMode)
                                            <textarea id="card-desc-editor" rows="10" wire:model="cardMarkdown" placeholder="Write descriptions with **bold text**, *italics*, headers, lists, etc..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[200px]"></textarea>
                                        @else
                                            <div class="prose max-w-none p-md min-h-[200px] bg-slate-50/30 text-on-surface text-sm overflow-y-auto">
                                                {!! \Illuminate\Support\Str::markdown($cardMarkdown ?: '*No text configured yet. Select the **Write** tab to start typing.*') !!}
                                            </div>
                                        @endif
                                    </div>
                                    @error('cardMarkdown')
                                        <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <!-- CATEGORY SLIDER ITEMS SELECTOR -->
                        @if($sectionType === 'category_slider')
                            <div class="space-y-md">
                                <span class="text-xs text-slate-400 font-semibold uppercase select-none block">Select Categories (Multi-select)</span>
                                
                                <div class="max-h-72 overflow-y-auto border border-outline-variant/20 rounded-xl p-md bg-slate-50 space-y-2">
                                    @php
                                        $renderCategoryTree = function($categories, $depth = 0) use (&$renderCategoryTree) {
                                            foreach ($categories as $cat) {
                                                @endphp
                                                <div x-data="{ expanded: false }" class="space-y-1">
                                                    <div style="padding-left: {{ $depth * 1.5 }}rem" class="flex items-center gap-1.5">
                                                        @if($cat->children->isNotEmpty())
                                                            <button type="button" @click="expanded = !expanded" class="p-1 hover:bg-slate-200 rounded flex items-center justify-center focus:outline-none text-slate-500 hover:text-slate-800 transition-colors">
                                                                <span class="material-symbols-outlined text-[18px] transition-transform duration-200 select-none" :class="expanded ? 'rotate-90' : ''">chevron_right</span>
                                                            </button>
                                                        @else
                                                            <div class="w-[26px]"></div>
                                                        @endif
                                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-outline-variant/20 cursor-pointer hover:bg-slate-100 transition-colors w-full">
                                                            <input type="checkbox" 
                                                                   value="{{ $cat->id }}" 
                                                                   wire:click="toggleCategorySelection({{ $cat->id }})"
                                                                   @if(in_array($cat->id, $this->selectedCategoryIds)) checked @endif
                                                                   class="rounded text-primary focus:ring-primary h-4 w-4 border-slate-300">
                                                            <span class="text-xs text-[#001229] font-bold truncate">
                                                                {{ $cat->name }}
                                                                @if($depth > 0)
                                                                    <span class="text-[9px] text-slate-400 font-normal ml-1">Subcategory</span>
                                                                @endif
                                                            </span>
                                                        </label>
                                                    </div>
                                                    @if($cat->children->isNotEmpty())
                                                        <div x-show="expanded" x-transition class="space-y-1">
                                                            @php
                                                            $renderCategoryTree($cat->children, $depth + 1);
                                                            @endphp
                                                        </div>
                                                    @endif
                                                </div>
                                                @php
                                            }
                                        };
                                        
                                        $rootCategories = \App\Models\Category::whereNull('parent_id')->with('children.children')->orderBy('sort_order')->orderBy('name')->get();
                                        $renderCategoryTree($rootCategories);
                                    @endphp
                                </div>

                                <div class="mt-4">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase select-none mb-2">Selected Categories in Order:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selectedCategoryIds as $id)
                                            @php $c = collect($categoriesList)->firstWhere('id', $id); @endphp
                                            @if($c)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#001229] text-white">
                                                    {{ $c->name }}
                                                    <button type="button" wire:click="toggleCategorySelection({{ $id }})" class="hover:text-gold focus:outline-none">
                                                        <span class="material-symbols-outlined text-sm font-bold">close</span>
                                                    </button>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    @error('selectedCategoryIds')
                                        <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <!-- PRODUCT SLIDER ITEM SELECTOR (Polished product picker) -->
                        @if($sectionType === 'product_slider')
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
                                
                                <!-- Products Grid & Filtering (8 cols) -->
                                <div class="lg:col-span-8 space-y-4">
                                    <!-- Search & Category Filters -->
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                                        <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Search by SKU, Name..." class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                        <select wire:model.live="productCategoryFilter" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                            <option value="">All Categories</option>
                                            @foreach($categoriesList as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="productStockFilter" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                            <option value="">All Stocks</option>
                                            <option value="in_stock">In Stock Only</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                        </select>
                                    </div>

                                    <!-- Product List -->
                                    <div class="space-y-2.5 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                                        @foreach($productsList as $prod)
                                            <div class="flex items-center justify-between p-3 bg-white border border-outline-variant/20 rounded-xl hover:bg-slate-50 transition-all">
                                                <div class="flex items-center gap-3">
                                                    @php
                                                        $primaryImage = $prod->primaryMedia ? $prod->primaryMedia->file_path : null;
                                                        $imageUrl = $primaryImage 
                                                            ? (str_starts_with($primaryImage, 'http') ? $primaryImage : Storage::disk('public')->url($primaryImage)) 
                                                            : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100';
                                                    @endphp
                                                    <img src="{{ $imageUrl }}" class="w-10 h-10 object-cover rounded-lg border bg-slate-50">
                                                    <div>
                                                        <h5 class="text-xs font-extrabold text-[#001229]">{{ $prod->title }}</h5>
                                                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5">SKU: {{ $prod->sku }} &bull; Price: ₹{{ number_format($prod->base_price, 2) }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="toggleProductSelection({{ $prod->id }})" class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors shadow-xs {{ in_array($prod->id, $this->selectedProductIds) ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/30 text-[#001229] hover:bg-slate-50' }}">
                                                    {{ in_array($prod->id, $this->selectedProductIds) ? 'Selected' : 'Select' }}
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Paginator Links -->
                                    <div class="mt-2 text-xs">
                                        {{ $productsList->links(data: ['scrollTo' => false]) }}
                                    </div>
                                </div>

                                <!-- Selected Products Panel (4 cols) -->
                                <div class="lg:col-span-4 border border-outline-variant/20 rounded-xl p-md bg-slate-50 flex flex-col max-h-[400px]">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block select-none mb-3">Selected Products Order:</span>
                                    
                                    <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                        @php $allProducts = \App\Models\Product::whereIn('id', $selectedProductIds)->get(); @endphp
                                        @foreach($selectedProductIds as $index => $id)
                                            @php $p = collect($allProducts)->firstWhere('id', $id); @endphp
                                            @if($p)
                                                <div class="flex items-center justify-between p-2.5 bg-white border border-outline-variant/20 rounded-lg text-xs">
                                                    <span class="font-semibold text-slate-700 truncate max-w-[120px]">{{ $p->title }}</span>
                                                    <button type="button" wire:click="toggleProductSelection({{ $id }})" class="text-rose-500 hover:text-rose-700">
                                                        <span class="material-symbols-outlined text-[18px] font-bold">close</span>
                                                    </button>
                                                </div>
                                            @endif
                                        @endforeach
                                        
                                        @if(empty($selectedProductIds))
                                            <div class="py-12 text-center text-slate-400 text-xs">
                                                <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">shopping_bag</span>
                                                <p>No products selected yet.</p>
                                            </div>
                                        @endif
                                    </div>
                                    @error('selectedProductIds')
                                        <span class="text-rose-600 text-[10px] font-bold mt-2 block">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        @endif

                        <!-- IMAGE SLIDER ITEMS -->
                        @if($sectionType === 'image_slider')
                            <div class="space-y-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs text-slate-400 font-semibold uppercase select-none block">Dynamic Slide Cards</span>
                                    <x-admin.button type="button" wire:click="addSlide" variant="outline" icon="add" class="text-xs">Add Slide</x-admin.button>
                                </div>

                                <div class="space-y-4 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($slides as $index => $slide)
                                        <div class="p-lg bg-white border border-outline-variant/20 rounded-xl shadow-xs relative space-y-4">
                                            <!-- Slide badge indicator -->
                                            <div class="flex justify-between items-center bg-slate-50 border border-outline-variant/10 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600">
                                                <span>Slide #{{ $index + 1 }}</span>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button" wire:click="moveSlide({{ $index }}, 'up')" @if($index === 0) disabled class="opacity-30 cursor-not-allowed" @endif class="text-slate-600 hover:text-[#001229]" title="Move Up">
                                                        <span class="material-symbols-outlined text-sm font-bold">arrow_upward</span>
                                                    </button>
                                                    <button type="button" wire:click="moveSlide({{ $index }}, 'down')" @if($index === count($slides) - 1) disabled class="opacity-30 cursor-not-allowed" @endif class="text-slate-600 hover:text-[#001229]" title="Move Down">
                                                        <span class="material-symbols-outlined text-sm font-bold">arrow_downward</span>
                                                    </button>
                                                    <button type="button" wire:click="removeSlide({{ $index }})" class="text-rose-500 hover:text-rose-700" title="Delete Slide">
                                                        <span class="material-symbols-outlined text-sm font-bold">delete</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-md">
                                                <div class="md:col-span-4 space-y-md">
                                                    <div>
                                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Slide Image*</label>
                                                        <div class="relative flex flex-col items-center justify-center border border-dashed border-slate-200 rounded-lg p-3 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer group min-h-[90px]">
                                                            <input type="file" wire:model="slides.{{ $index }}.upload" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                                            <div class="text-center space-y-1 z-0">
                                                                <span class="material-symbols-outlined text-slate-400 text-2xl group-hover:text-primary transition-colors select-none">cloud_upload</span>
                                                                <p class="text-[10px] font-bold text-slate-600 group-hover:text-primary transition-colors">Choose Image File</p>
                                                            </div>
                                                            <!-- Loading indicator overlay -->
                                                            <div wire:loading wire:target="slides.{{ $index }}.upload" class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center rounded-lg z-20 space-y-1">
                                                                <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                                                <span class="text-[9px] font-bold text-primary">Uploading...</span>
                                                            </div>
                                                        </div>
                                                        @error("slides.{$index}.upload") <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                                        
                                                        @if(isset($slide['upload']))
                                                            <div class="mt-2 rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs bg-slate-900">
                                                                <img src="{{ $slide['upload']->temporaryUrl() }}" class="w-full h-auto object-contain block max-h-48">
                                                                <div class="px-2 py-1 bg-slate-900/80 text-[9px] text-white font-bold truncate">
                                                                    {{ $slide['upload']->getClientOriginalName() }}
                                                                </div>
                                                            </div>
                                                        @elseif(isset($slide['existing_image']))
                                                            <div class="mt-2 rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs bg-slate-900">
                                                                <img src="{{ asset('storage/' . $slide['existing_image']) }}" class="w-full h-auto object-contain block max-h-48">
                                                                <div class="px-2 py-1 bg-slate-900/80 text-[9px] text-white font-bold">
                                                                    Current Image
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="md:col-span-8 space-y-md pl-md">
                                                    <div class="grid grid-cols-2 gap-md">
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Slide Title</label>
                                                            <input type="text" wire:model="slides.{{ $index }}.title" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                        </div>
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Slide Subtitle</label>
                                                            <input type="text" wire:model="slides.{{ $index }}.subtitle" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-md">
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">CTA Button Label</label>
                                                            <input type="text" wire:model="slides.{{ $index }}.cta_label" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                        </div>
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Link Type</label>
                                                            <select wire:model.live="slides.{{ $index }}.link_type" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                <option value="none">No Link</option>
                                                                <option value="category">Linked Category</option>
                                                                <option value="product">Linked Product</option>
                                                                <option value="url">External Redirect URL</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    @if(($slide['link_type'] ?? 'none') === 'category')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Select Linked Category</label>
                                                            @php
                                                                $selectedSlideImageCatId = $slide['link_category_id'] ?? '';
                                                                $selectedSlideImageCat = $selectedSlideImageCatId === 'all' || $selectedSlideImageCatId == '0' || $selectedSlideImageCatId == ''
                                                                    ? (object)['name' => 'All Categories (Main Catalog)'] 
                                                                    : collect($categoriesList)->firstWhere('id', $selectedSlideImageCatId);
                                                                $selectedSlideImageCatName = $selectedSlideImageCat ? $selectedSlideImageCat->name : 'Select Category';
                                                            @endphp
                                                            <div x-data="{ open: false, search: '', selectedName: '{{ addslashes($selectedSlideImageCatName) }}' }" class="relative">
                                                                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full flex items-center justify-between text-left text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                    <span x-text="selectedName"></span>
                                                                    <span class="material-symbols-outlined text-slate-400 text-[18px] select-none">arrow_drop_down</span>
                                                                </button>
                                                                
                                                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-outline-variant/30 rounded-lg shadow-lg p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                                                    <!-- Search field -->
                                                                    <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 border border-outline-variant/20 rounded-md mb-2">
                                                                        <span class="material-symbols-outlined text-slate-400 text-[16px] select-none">search</span>
                                                                        <input type="text" x-model="search" placeholder="Search category name..." class="w-full bg-transparent border-0 p-0 text-xs focus:ring-0 outline-none h-6">
                                                                    </div>
                                                                    
                                                                    <!-- Category list -->
                                                                    <div class="space-y-1">
                                                                        <div class="flex items-center gap-1 hover:bg-slate-50 rounded-lg p-1">
                                                                            <div class="w-6"></div>
                                                                            <button type="button" 
                                                                                    wire:click="$set('slides.{{ $index }}.link_category_id', 'all')"
                                                                                    @click="open = false; selectedName = 'All Categories (Main Catalog)'"
                                                                                    class="text-left text-xs text-slate-700 hover:text-primary font-bold flex-1 py-0.5">
                                                                                All Categories (Main Catalog)
                                                                            </button>
                                                                        </div>
                                                                        @php
                                                                            $rootCategoriesSelectSliderImage = \App\Models\Category::whereNull('parent_id')->with('children.children')->orderBy('sort_order')->orderBy('name')->get();
                                                                            $renderCategorySelectTree($rootCategoriesSelectSliderImage, 'slides.' . $index . '.link_category_id');
                                                                        @endphp
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif(($slide['link_type'] ?? 'none') === 'product')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Select Linked Product</label>
                                                            <select wire:model="slides.{{ $index }}.link_product_id" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                <option value="">Select Product</option>
                                                                @php $allProds = \App\Models\Product::where('is_active', true)->orderBy('title')->get(); @endphp
                                                                @foreach($allProds as $prod)
                                                                    <option value="{{ $prod->id }}">{{ $prod->title }} (SKU: {{ $prod->sku }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @elseif(($slide['link_type'] ?? 'none') === 'url')
                                                        <div>
                                                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">External URL</label>
                                                            <input type="text" wire:model="slides.{{ $index }}.external_url" placeholder="https://example.com" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if(empty($slides))
                                        <div class="py-12 border rounded-xl border-dashed text-center text-slate-400 text-xs">
                                            <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">view_carousel</span>
                                            <p>No slide cards added yet. Click "Add Slide" to begin.</p>
                                        </div>
                                    @endif
                                </div>
                                @error('slides')
                                    <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                    </div>
                @endif

                <!-- STEP 4: Live Preview Simulation Mockup -->
                @if($wizardStep === 4)
                    <div class="space-y-lg">
                        <h4 class="font-title-lg text-[#001229]">Dynamic Section Live Preview</h4>
                        
                        <div class="p-lg bg-slate-900 border rounded-2xl relative shadow-inner overflow-hidden select-none min-h-[30vh]">
                            <div class="absolute top-2 right-2 px-2 py-0.5 rounded bg-primary/20 text-white text-[9px] font-bold border border-primary/30 uppercase tracking-widest">Portal Mockup Preview</div>
                            
                            <div class="space-y-4 text-white">
                                <!-- Banner type preview -->
                                @if($sectionType === 'banner')
                                    <div class="relative w-full rounded-xl overflow-hidden min-h-[220px] bg-slate-800 flex items-center justify-center p-6 border border-white/5">
                                        @if($bannerImage)
                                            <img src="{{ $bannerImage->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                        @elseif($bannerExistingImage)
                                            <img src="{{ asset('storage/' . $bannerExistingImage) }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="text-slate-500 flex flex-col items-center">
                                                <span class="material-symbols-outlined text-4xl mb-1">image</span>
                                                <span class="text-xs">No banner cover uploaded</span>
                                            </div>
                                        @endif
                                        @if($bannerCtaLabel)
                                            <div class="relative z-10 text-center space-y-2 max-w-md">
                                                <button type="button" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-xs font-bold transition-all shadow-md mt-2">
                                                    {{ $bannerCtaLabel }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Banner Slider preview -->
                                @if($sectionType === 'banner_slider')
                                    <div class="relative w-full rounded-xl overflow-hidden min-h-[220px] bg-slate-800 flex items-center justify-center p-6 border border-white/5">
                                        @if(!empty($slides))
                                            @php $firstSlide = $slides[0]; @endphp
                                            @if(isset($firstSlide['upload']))
                                                <img src="{{ $firstSlide['upload']->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                            @elseif(isset($firstSlide['existing_image']))
                                                <img src="{{ asset('storage/' . $firstSlide['existing_image']) }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                            @endif
                                            @if(!empty($firstSlide['cta_label']))
                                                <div class="relative z-10 text-center space-y-2 max-w-md">
                                                    <button type="button" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-xs font-bold transition-all shadow-md mt-2">
                                                        {{ $firstSlide['cta_label'] }}
                                                    </button>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-slate-500 flex flex-col items-center">
                                                <span class="material-symbols-outlined text-4xl mb-1">view_carousel</span>
                                                <span class="text-xs">No banner slides added yet</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if(count($slides) > 1)
                                        <div class="flex items-center justify-center gap-1.5 mt-2">
                                            @foreach($slides as $i => $s)
                                                <span class="w-1.5 h-1.5 rounded-full {{ $i === 0 ? 'bg-gold' : 'bg-white/30' }}"></span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                <!-- Image/Text Card preview -->
                                @if($sectionType === 'image_text_card')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-800 p-4 rounded-xl border border-white/5 text-xs text-slate-200">
                                        @if($cardAlignment === 'left')
                                            <div class="h-40 rounded-lg overflow-hidden bg-slate-700 relative">
                                                @if($cardImage)
                                                    <img src="{{ $cardImage->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                                                @elseif($cardExistingImage)
                                                    <img src="{{ asset('storage/' . $cardExistingImage) }}" class="absolute inset-0 w-full h-full object-cover">
                                                @else
                                                    <div class="flex items-center justify-center h-full text-slate-500">
                                                        <span class="material-symbols-outlined text-3xl">image</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="overflow-y-auto max-h-40 p-2 prose prose-invert font-mono text-[10px]">
                                                {!! \Illuminate\Support\Str::markdown($cardMarkdown ?: 'No text configured yet.') !!}
                                            </div>
                                        @else
                                            <div class="overflow-y-auto max-h-40 p-2 prose prose-invert font-mono text-[10px]">
                                                {!! \Illuminate\Support\Str::markdown($cardMarkdown ?: 'No text configured yet.') !!}
                                            </div>
                                            <div class="h-40 rounded-lg overflow-hidden bg-slate-700 relative">
                                                @if($cardImage)
                                                    <img src="{{ $cardImage->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                                                @elseif($cardExistingImage)
                                                    <img src="{{ asset('storage/' . $cardExistingImage) }}" class="absolute inset-0 w-full h-full object-cover">
                                                @else
                                                    <div class="flex items-center justify-center h-full text-slate-500">
                                                        <span class="material-symbols-outlined text-3xl">image</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif                                <!-- Category Slider Preview -->
                                @if($sectionType === 'category_slider')
                                    <div class="space-y-3">
                                        <div class="flex flex-col gap-0.5">
                                            <h4 class="text-sm font-black text-white leading-none">{{ $sectionTitle ?: 'Category Products' }}</h4>
                                            @if($sectionSubtitle) <p class="text-[10px] text-slate-400 font-semibold leading-none">{{ $sectionSubtitle }}</p> @endif
                                        </div>

                                        <div class="flex items-center gap-3 overflow-x-auto pb-2 scroll-smooth">
                                            @php
                                                $catProducts = \App\Models\Product::where('is_active', true)
                                                    ->whereHas('categories', function($q) {
                                                        $q->whereIn('categories.id', $this->selectedCategoryIds);
                                                    })
                                                    ->with('primaryMedia')
                                                    ->take(6)
                                                    ->get();
                                            @endphp
                                            @forelse($catProducts as $p)
                                                <div class="flex-shrink-0 w-32 p-2.5 bg-white/5 border border-white/10 rounded-xl space-y-2">
                                                    @php
                                                        $primaryImage = $p->primaryMedia ? $p->primaryMedia->file_path : null;
                                                        $imageUrl = $primaryImage 
                                                            ? (str_starts_with($primaryImage, 'http') ? $primaryImage : asset('storage/' . $primaryImage)) 
                                                            : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100';
                                                    @endphp
                                                    <img src="{{ $imageUrl }}" class="w-full h-16 object-cover rounded-lg border border-white/5 bg-slate-800">
                                                    <div class="space-y-1">
                                                        <h5 class="text-[10px] font-bold truncate leading-none">{{ $p->title }}</h5>
                                                        <p class="text-[9px] text-gold font-bold leading-none">₹{{ number_format($p->base_price, 2) }}</p>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-slate-500 text-xs py-4">No products found in the selected categories.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif

                                <!-- Product Slider Preview -->
                                @if($sectionType === 'product_slider')
                                    <div class="space-y-3">
                                        <div class="flex flex-col gap-0.5">
                                            <h4 class="text-sm font-black text-white leading-none">{{ $sectionTitle ?: 'Featured Products' }}</h4>
                                            @if($sectionSubtitle) <p class="text-[10px] text-slate-400 font-semibold leading-none">{{ $sectionSubtitle }}</p> @endif
                                        </div>

                                        <div class="flex items-center gap-3 overflow-x-auto pb-2 scroll-smooth">
                                            @php $allProds = \App\Models\Product::whereIn('id', $selectedProductIds)->with('primaryMedia')->get(); @endphp
                                            @foreach($selectedProductIds as $id)
                                                @php $p = collect($allProds)->firstWhere('id', $id); @endphp
                                                @if($p)
                                                    <div class="flex-shrink-0 w-32 p-2.5 bg-white/5 border border-white/10 rounded-xl space-y-2">
                                                        @php
                                                            $primaryImage = $p->primaryMedia ? $p->primaryMedia->file_path : null;
                                                            $imageUrl = $primaryImage 
                                                                ? (str_starts_with($primaryImage, 'http') ? $primaryImage : asset('storage/' . $primaryImage)) 
                                                                : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100';
                                                        @endphp
                                                        <img src="{{ $imageUrl }}" class="w-full h-16 object-cover rounded-lg border border-white/5 bg-slate-800">
                                                        <div class="space-y-1">
                                                            <h5 class="text-[10px] font-bold truncate leading-none">{{ $p->title }}</h5>
                                                            <p class="text-[9px] text-gold font-bold leading-none">₹{{ number_format($p->base_price, 2) }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Image Slider Preview -->
                                @if($sectionType === 'image_slider')
                                    <div class="space-y-3">
                                        {{-- Section header --}}
                                        @if($sectionTitle)
                                            <div class="flex flex-col gap-0.5">
                                                <h4 class="text-sm font-black text-white leading-none">{{ $sectionTitle }}</h4>
                                                @if($sectionSubtitle)<p class="text-[10px] text-slate-400 font-semibold leading-none">{{ $sectionSubtitle }}</p>@endif
                                            </div>
                                        @endif

                                        @if(!empty($slides))
                                            {{-- Carousel strip --}}
                                            <div class="flex items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                                                @foreach($slides as $i => $slide)
                                                    <div class="flex-shrink-0 w-48 rounded-xl overflow-hidden border border-white/10 bg-slate-800 relative group">
                                                        {{-- Image --}}
                                                        @if(isset($slide['upload']))
                                                            <img src="{{ $slide['upload']->temporaryUrl() }}" class="w-full h-36 object-cover block">
                                                        @elseif(isset($slide['existing_image']))
                                                            <img src="{{ asset('storage/' . $slide['existing_image']) }}" class="w-full h-36 object-cover block">
                                                        @else
                                                            <div class="w-full h-36 bg-slate-700 flex items-center justify-center">
                                                                <span class="material-symbols-outlined text-slate-500 text-2xl">image</span>
                                                            </div>
                                                        @endif

                                                        {{-- Overlay with title/cta --}}
                                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent flex flex-col justify-end p-2.5 space-y-1">
                                                            @if(!empty($slide['title']))
                                                                <p class="text-[11px] font-black text-white leading-tight truncate">{{ $slide['title'] }}</p>
                                                            @endif
                                                            @if(!empty($slide['subtitle']))
                                                                <p class="text-[9px] text-slate-300 leading-tight truncate">{{ $slide['subtitle'] }}</p>
                                                            @endif
                                                            @if(!empty($slide['cta_label']))
                                                                <span class="inline-flex items-center gap-1 self-start px-2 py-0.5 rounded-full bg-gold/90 text-[#001229] text-[9px] font-black">
                                                                    {{ $slide['cta_label'] }}
                                                                    <span class="material-symbols-outlined text-[10px]">arrow_forward</span>
                                                                </span>
                                                            @endif
                                                        </div>

                                                        {{-- Slide number badge --}}
                                                        <div class="absolute top-1.5 left-1.5 w-5 h-5 rounded-full bg-black/50 flex items-center justify-center text-[9px] font-black text-white">
                                                            {{ $i + 1 }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Dot indicators --}}
                                            @if(count($slides) > 1)
                                                <div class="flex items-center justify-center gap-1.5">
                                                    @foreach($slides as $i => $s)
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $i === 0 ? 'bg-gold' : 'bg-white/30' }}"></span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            <div class="rounded-xl border border-dashed border-white/20 bg-slate-800/50 flex flex-col items-center justify-center py-10 text-slate-500">
                                                <span class="material-symbols-outlined text-3xl mb-1">view_carousel</span>
                                                <span class="text-xs">No slides added yet — go back to Step 3</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Live warnings strip -->
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-amber-600 text-sm select-none mt-0.5">info</span>
                            <div class="text-xs text-amber-800 leading-relaxed">
                                <strong>Visibility check:</strong> Ensure the section status is set to active. If you configured visibilty dates, it will automatically schedule appearance on the customer portal in display order.
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Footer Navigation Controls -->
            <div class="border-t border-outline-variant/30 pt-lg mt-xl flex items-center justify-end gap-md">
                @if($wizardStep > 1)
                    <x-admin.button type="button" wire:click="prevStep" variant="outline" icon="arrow_back">Back</x-admin.button>
                @else
                    <x-admin.button type="button" wire:click="$set('showModal', false)" variant="ghost">Cancel</x-admin.button>
                @endif

                @if($editingSectionId)
                    <x-admin.button type="button" wire:click="saveSection" variant="secondary" class="bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600" icon="save">Save Changes</x-admin.button>
                @endif

                @if($wizardStep < 4)
                    <x-admin.button type="button" wire:click="nextStep" variant="primary" icon="arrow_forward">Next</x-admin.button>
                @else
                    @if(!$editingSectionId)
                        <x-admin.button type="button" wire:click="saveSection" variant="primary" icon="save">Save Section</x-admin.button>
                    @endif
                @endif
            </div>

        </div>
    </x-admin.modal>

    <!-- Section deletion confirmation modal -->
    <x-admin.modal id="delete-section-modal" title="Delete Homepage Block" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg">
                <span class="material-symbols-outlined text-[32px] text-error select-none">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This content block will be permanently removed from the customer home page portal. Historical ordering logs will not be affected.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="deleteSection" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Block</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <script>
        function insertMarkdownCard(tagOpen, tagClose = '') {
            const ta = document.getElementById('card-desc-editor');
            if (!ta) return;
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            const text = ta.value;
            const selected = text.substring(start, end);
            const replacement = tagOpen + selected + tagClose;
            
            ta.value = text.substring(0, start) + replacement + text.substring(end);
            
            ta.focus();
            if (start === end) {
                const newCursorPos = start + tagOpen.length;
                ta.setSelectionRange(newCursorPos, newCursorPos);
            } else {
                ta.setSelectionRange(start + tagOpen.length, start + tagOpen.length + selected.length);
            }
            ta.dispatchEvent(new Event('input'));
        }
    </script>
    <style>
        .prose h1 { font-size: 1.8em; font-weight: 800; margin-top: 0.8em; margin-bottom: 0.4em; color: #0f172a; }
        .prose h2 { font-size: 1.5em; font-weight: 700; margin-top: 0.8em; margin-bottom: 0.4em; color: #0f172a; }
        .prose h3 { font-size: 1.25em; font-weight: 600; margin-top: 0.8em; margin-bottom: 0.4em; color: #0f172a; }
        .prose p { margin-top: 0.4em; margin-bottom: 0.8em; line-height: 1.6; color: #334155; }
        .prose ul { list-style-type: disc !important; padding-left: 1.5rem !important; margin-top: 0.4em; margin-bottom: 0.8em; }
        .prose ol { list-style-type: decimal !important; padding-left: 1.5rem !important; margin-top: 0.4em; margin-bottom: 0.8em; }
        .prose li { margin-bottom: 0.25em; }
        .prose blockquote { border-left: 4px solid #cbd5e1; padding-left: 1rem; italic: true; color: #475569; margin: 0.8em 0; }
        .prose a { color: #5c44c4; text-decoration: underline; font-weight: 500; }
        .prose strong { font-weight: 700; color: #0f172a; }
        .prose em { font-style: italic; }
    </style>
</div>
