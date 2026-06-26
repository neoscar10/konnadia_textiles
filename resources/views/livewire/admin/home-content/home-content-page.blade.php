<div>
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
                <div class="flex items-center gap-2 {{ $wizardStep === 1 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 1 ? 'bg-primary text-white border-primary' : '' }}">1</span>
                    <span>Choose Type</span>
                </div>
                <div class="w-12 h-px bg-slate-200"></div>
                <div class="flex items-center gap-2 {{ $wizardStep === 2 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 2 ? 'bg-primary text-white border-primary' : '' }}">2</span>
                    <span>Configure Layout</span>
                </div>
                <div class="w-12 h-px bg-slate-200"></div>
                <div class="flex items-center gap-2 {{ $wizardStep === 3 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 3 ? 'bg-primary text-white border-primary' : '' }}">3</span>
                    <span>Add/Select Items</span>
                </div>
                <div class="w-12 h-px bg-slate-200"></div>
                <div class="flex items-center gap-2 {{ $wizardStep === 4 ? 'text-primary font-black' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $wizardStep >= 4 ? 'bg-primary text-white border-primary' : '' }}">4</span>
                    <span>Live Preview</span>
                </div>
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
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">A full-width high-quality cover image banner. Perfect for running targeted launch campaigns or seasonal store sales with customizable CTAs.</p>
                                </div>
                            </button>

                            <!-- Category Slider -->
                            <button type="button" wire:click="$set('sectionType', 'category_slider')" class="flex items-start text-left gap-md p-lg border rounded-xl hover:border-primary/50 transition-all focus:outline-none {{ $sectionType === 'category_slider' ? 'border-primary bg-primary/[0.02] ring-2 ring-primary/20' : 'border-outline-variant/30' }}">
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">widgets</span>
                                <div>
                                    <h5 class="font-bold text-sm text-[#001229]">Category Quick Slider</h5>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">A horizontal carousel of your wholesale product categories. Promotes discovery by letting partners jump straight to filtered listings.</p>
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
                                <span class="material-symbols-outlined text-[32px] text-primary mt-1 select-none">view_carousel</span>
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
                        @if($sectionType !== 'banner')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                                <div>
                                    <label class="text-xs text-slate-500 font-bold block mb-1.5">Display Style</label>
                                    <select wire:model="sectionDisplayStyle" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                        <option value="">Default Style</option>
                                        @if($sectionType === 'category_slider')
                                            <option value="cards">Circular Cards</option>
                                            <option value="compact">Compact Grid</option>
                                            <option value="image_cards">Cover Image Cards</option>
                                        @elseif($sectionType === 'image_slider')
                                            <option value="hero">Hero Slider (Large)</option>
                                            <option value="compact">Compact Banner Slider</option>
                                        @else
                                            <option value="cards">Standard Cards</option>
                                            <option value="compact">List Layout</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500 font-bold block mb-1.5">Items Per View</label>
                                    <input type="number" wire:model="sectionItemsPerView" min="1" max="10" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500 font-bold block mb-1.5">Display Limit</label>
                                    <input type="number" wire:model="sectionDisplayLimit" min="1" max="50" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                </div>
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
                                <div class="md:col-span-5 space-y-md">
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Banner Image Cover (Ratio 16:5 / 16:6)*</label>
                                        <div class="flex items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-6 bg-slate-50 hover:bg-slate-100 transition-colors relative">
                                            <input type="file" wire:model="bannerImage" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <div class="text-center">
                                                <span class="material-symbols-outlined text-slate-400 text-3xl select-none">cloud_upload</span>
                                                <p class="text-xs font-semibold text-slate-500 mt-1">Drag file or click to select</p>
                                                <p class="text-[10px] text-slate-400 mt-0.5">JPEG, PNG, WEBP (Max 5MB)</p>
                                            </div>
                                        </div>
                                        @error('bannerImage')
                                            <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                                        @enderror

                                        @if($bannerImage)
                                            <div class="mt-3">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase select-none">Temporary upload preview:</p>
                                                <img src="{{ $bannerImage->temporaryUrl() }}" class="h-24 w-full object-cover rounded-lg border mt-1.5">
                                            </div>
                                        @elseif($bannerExistingImage)
                                            <div class="mt-3">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase select-none">Current live image:</p>
                                                <img src="{{ Storage::disk('public')->url($bannerExistingImage) }}" class="h-24 w-full object-cover rounded-lg border mt-1.5">
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Image Alt text</label>
                                        <input type="text" wire:model="bannerImageAlt" placeholder="e.g. Promotional banner for cotton apparel" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                    </div>
                                </div>

                                <div class="md:col-span-7 space-y-md">
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Banner Custom Title (Optional)</label>
                                        <input type="text" wire:model="bannerTitle" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Banner Subtitle (Optional)</label>
                                        <input type="text" wire:model="bannerSubtitle" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div class="grid grid-cols-2 gap-md">
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">CTA Button Label</label>
                                            <input type="text" wire:model="bannerCtaLabel" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">Link Destination Type</label>
                                            <select wire:model.live="bannerLinkType" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                <option value="none">No Link</option>
                                                <option value="category">Linked Category</option>
                                                <option value="product">Linked Product</option>
                                                <option value="url">External Redirect URL</option>
                                            </select>
                                        </div>
                                    </div>

                                    @if($bannerLinkType === 'category')
                                        <div>
                                            <label class="text-xs text-slate-500 font-bold block mb-1.5">Select Linked Category</label>
                                            <select wire:model="bannerLinkCategoryId" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                <option value="">Select Category</option>
                                                @foreach($categoriesList as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
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

                        <!-- CATEGORY SLIDER ITEMS SELECTOR -->
                        @if($sectionType === 'category_slider')
                            <div class="space-y-md">
                                <span class="text-xs text-slate-400 font-semibold uppercase select-none block">Select Categories (Multi-select)</span>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-md max-h-72 overflow-y-auto border border-outline-variant/20 rounded-xl p-md bg-slate-50">
                                    @foreach($categoriesList as $cat)
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-outline-variant/20 cursor-pointer hover:bg-slate-100 transition-colors">
                                            <input type="checkbox" 
                                                   value="{{ $cat->id }}" 
                                                   wire:click="toggleCategorySelection({{ $cat->id }})"
                                                   @if(in_array($cat->id, $this->selectedCategoryIds)) checked @endif
                                                   class="rounded text-primary focus:ring-primary h-4 w-4 border-slate-300">
                                            <span class="text-xs text-[#001229] font-bold truncate">{{ $cat->name }}</span>
                                        </label>
                                    @endforeach
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
                                                <div class="md:col-span-5 space-y-md">
                                                    <div>
                                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Upload Image*</label>
                                                        <input type="file" wire:model="slides.{{ $index }}.upload" class="w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                                        @error("slides.{$index}.upload") <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                                                        
                                                        @if(isset($slide['upload']))
                                                            <div class="mt-2 text-[10px] text-slate-400">File Selected: {{ $slide['upload']->getClientOriginalName() }}</div>
                                                        @elseif(isset($slide['existing_image']))
                                                            <div class="mt-2">
                                                                <img src="{{ Storage::disk('public')->url($slide['existing_image']) }}" class="h-10 w-24 object-cover rounded border">
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Image Alt text</label>
                                                        <input type="text" wire:model="slides.{{ $index }}.image_alt" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                    </div>
                                                </div>

                                                <div class="md:col-span-7 space-y-md">
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
                                                            <select wire:model="slides.{{ $index }}.link_category_id" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-2.5 focus:outline-none focus:ring-1 focus:ring-gold">
                                                                <option value="">Select Category</option>
                                                                @foreach($categoriesList as $cat)
                                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                                @endforeach
                                                            </select>
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
                                            <img src="{{ Storage::disk('public')->url($bannerExistingImage) }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="text-slate-500 flex flex-col items-center">
                                                <span class="material-symbols-outlined text-4xl mb-1">image</span>
                                                <span class="text-xs">No banner cover uploaded</span>
                                            </div>
                                        @endif
                                        <div class="relative z-10 text-center space-y-2 max-w-md">
                                            <h3 class="text-xl font-black tracking-tight leading-tight">{{ $bannerTitle ?: $sectionTitle ?: 'Summer wholesale promotion' }}</h3>
                                            <p class="text-xs font-semibold text-slate-200/90 leading-relaxed">{{ $bannerSubtitle ?: $sectionSubtitle ?: 'Check out catalog updates' }}</p>
                                            <button type="button" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-xs font-bold transition-all shadow-md mt-2">
                                                {{ $bannerCtaLabel ?: 'Shop Now' }}
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <!-- Category Slider Preview -->
                                @if($sectionType === 'category_slider')
                                    <div class="space-y-3">
                                        <div class="flex flex-col gap-0.5">
                                            <h4 class="text-sm font-black text-white leading-none">{{ $sectionTitle ?: 'Shop by Category' }}</h4>
                                            @if($sectionSubtitle) <p class="text-[10px] text-slate-400 font-semibold leading-none">{{ $sectionSubtitle }}</p> @endif
                                        </div>

                                        <div class="flex items-center gap-3 overflow-x-auto pb-2 scroll-smooth">
                                            @foreach($selectedCategoryIds as $id)
                                                @php $c = collect($categoriesList)->firstWhere('id', $id); @endphp
                                                @if($c)
                                                    <div class="flex-shrink-0 w-24 p-3 bg-white/5 border border-white/10 rounded-xl text-center space-y-2">
                                                        <div class="w-10 h-10 rounded-full bg-gold/10 text-gold flex items-center justify-center mx-auto">
                                                            <span class="material-symbols-outlined text-lg font-bold select-none">widgets</span>
                                                        </div>
                                                        <h5 class="text-[10px] font-bold tracking-tight truncate leading-none">{{ $c->name }}</h5>
                                                    </div>
                                                @endif
                                            @endforeach
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
                                                                ? (str_starts_with($primaryImage, 'http') ? $primaryImage : Storage::disk('public')->url($primaryImage)) 
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
                                    <div class="relative w-full rounded-xl overflow-hidden min-h-[200px] bg-slate-800 flex items-center justify-center p-6 border border-white/5">
                                        @if(!empty($slides))
                                            @php $firstSlide = $slides[0]; @endphp
                                            @if(isset($firstSlide['upload']))
                                                <img src="{{ $firstSlide['upload']->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                            @elseif(isset($firstSlide['existing_image']))
                                                <img src="{{ Storage::disk('public')->url($firstSlide['existing_image']) }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                                            @endif
                                            <div class="relative z-10 text-center space-y-2 max-w-md">
                                                <h3 class="text-xl font-black tracking-tight leading-tight">{{ $firstSlide['title'] ?: 'Promo Slide Cover' }}</h3>
                                                <p class="text-xs font-semibold text-slate-200/90 leading-relaxed">{{ $firstSlide['subtitle'] ?: 'Check out catalog campaigns' }}</p>
                                                <button type="button" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-xs font-bold transition-all shadow-md mt-2">
                                                    {{ $firstSlide['cta_label'] ?: 'Shop Now' }}
                                                </button>
                                            </div>
                                        @else
                                            <div class="text-slate-500 flex flex-col items-center">
                                                <span class="material-symbols-outlined text-4xl mb-1">view_carousel</span>
                                                <span class="text-xs">No slides uploaded yet</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if(count($slides) > 1)
                                        <div class="flex items-center justify-center gap-1.5">
                                            @foreach($slides as $i => $s)
                                                <span class="w-1.5 h-1.5 rounded-full {{ $i === 0 ? 'bg-gold' : 'bg-white/30' }}"></span>
                                            @endforeach
                                        </div>
                                    @endif
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
</div>
