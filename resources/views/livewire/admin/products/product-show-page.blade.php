<div>
    <x-slot:title>Product Details</x-slot:title>

    @if($product)
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl select-none">
            <div class="flex items-center gap-md">
                <a href="{{ route('admin.products.index') }}" class="w-10 h-10 bg-surface-container-low rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-headline-lg text-primary tracking-tight flex items-center gap-sm">
                        {{ $product->title }}
                        <x-admin.badge type="{{ $product->is_active ? 'success' : 'default' }}" class="ml-sm text-xs">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </h1>
                    <p class="font-body-md text-on-surface-variant font-mono">
                        {{ $product->sku }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-xl pb-xl">
            <!-- Left Column (8 cols) -->
            <div class="col-span-12 lg:col-span-8 space-y-xl">
                <!-- Basic Information -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">inventory_2</span>
                        <h3 class="font-title-md text-primary">Basic Information</h3>
                    </x-slot:header>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">Product Title</span>
                            <span class="font-bold text-primary text-base">{{ $product->title }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">Base Price (MRP)</span>
                            <span class="font-bold text-primary text-base">₹{{ number_format($product->base_price, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">Product SKU</span>
                            <span class="font-mono text-primary font-bold">{{ $product->sku }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-xs text-on-surface-variant block select-none mb-xs">Description</span>
                            <div class="prose max-w-none p-md border rounded-lg bg-surface-container-low text-on-surface text-sm">
                                {!! Illuminate\Support\Str::markdown($product->description ?? '') !!}
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <!-- Media Gallery -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">image</span>
                        <h3 class="font-title-md text-primary">Media Gallery</h3>
                    </x-slot:header>

                    <div class="flex flex-wrap gap-md">
                        @forelse($product->media as $m)
                            <div class="w-32 h-32 rounded-lg border overflow-hidden bg-surface-container-low relative border-outline-variant/30 flex items-center justify-center">
                                <img src="{{ Storage::url($m->file_path) }}" class="w-full h-full object-cover">
                                @if($m->is_primary)
                                    <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-black/60 text-white text-[10px] rounded select-none font-bold">Cover</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-on-surface-variant select-none">No media uploaded for this product.</p>
                        @endforelse
                    </div>
                </x-admin.card>

                <!-- Variations & Combinations Matrix -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">style</span>
                        <h3 class="font-title-md text-primary">Variants & Stock Matrix</h3>
                    </x-slot:header>

                    @if($product->combinations->count() > 0)
                        <x-slot:bodyClass>p-0</x-slot:bodyClass>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left font-body-md text-sm">
                                <thead class="bg-surface-container text-on-surface-variant font-bold uppercase text-xs border-b border-outline-variant/20 select-none">
                                    <tr>
                                        <th class="px-lg py-sm">Combination Values</th>
                                        <th class="px-lg py-sm">SKU Override</th>
                                        <th class="px-lg py-sm text-center">Stock</th>
                                        <th class="px-lg py-sm text-right">Price (Selling / Override)</th>
                                        <th class="px-lg py-sm text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/10">
                                    @foreach($product->combinations as $comb)
                                        <tr>
                                            <td class="px-lg py-md font-bold text-primary">
                                                {{ collect($comb->combination_values)->map(fn($v, $k) => "$k: $v")->implode(', ') }}
                                            </td>
                                            <td class="px-lg py-md font-mono text-on-surface-variant text-xs">
                                                {{ $comb->sku ?: '-' }}
                                            </td>
                                            <td class="px-lg py-md text-center font-semibold text-secondary">
                                                {{ $comb->stock_quantity }}
                                            </td>
                                            <td class="px-lg py-md text-right font-bold text-primary">
                                                ₹{{ number_format($comb->price !== null ? (float)$comb->price : $product->base_price, 2) }}
                                                @if($comb->price === null)
                                                    <span class="text-[10px] text-on-surface-variant font-normal block select-none">(Base Price)</span>
                                                @endif
                                            </td>
                                            <td class="px-lg py-md text-center">
                                                <x-admin.badge type="{{ $comb->is_active ? 'success' : 'default' }}">
                                                    {{ $comb->is_active ? 'Active' : 'Inactive' }}
                                                </x-admin.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="select-none">
                            <span class="text-xs text-on-surface-variant block">Non-Variant Inventory Stock</span>
                            <span class="font-bold text-lg text-primary">{{ $product->stock_quantity }} items in stock</span>
                        </div>
                    @endif
                </x-admin.card>
            </div>

            <!-- Right Column (4 cols) -->
            <div class="col-span-12 lg:col-span-4 space-y-xl">
                <!-- Assigned Categories -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">folder</span>
                        <h3 class="font-title-md text-primary">Assigned Categories</h3>
                    </x-slot:header>

                    <div class="space-y-sm select-none">
                        @forelse($product->categories as $cat)
                            <div class="flex items-center gap-xs text-sm font-semibold text-secondary">
                                <span class="material-symbols-outlined text-[16px]">folder_open</span>
                                <span>{{ $cat->name }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-on-surface-variant">Uncategorized</p>
                        @endforelse
                    </div>
                </x-admin.card>

                <!-- Customer Level Selling Prices -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">payments</span>
                        <h3 class="font-title-md text-primary">B2B Pricing Matrix</h3>
                    </x-slot:header>

                    <div class="space-y-md select-none">
                        @foreach($customerLevels as $level)
                            @php
                                $priceRecord = $product->customerLevelPrices->where('customer_level_id', $level->id)->first();
                                $disc = $priceRecord ? (float)$priceRecord->discount_percentage : (float)$level->discount_percentage;
                                $calculatedSellingPrice = $product->base_price * (1 - ($disc / 100));
                                $isMarkup = $disc < 0;
                            @endphp
                            <div class="flex justify-between items-center p-sm bg-surface-container-low border border-outline-variant/30 rounded-lg">
                                <div class="flex flex-col">
                                    <span class="font-bold text-primary text-xs">{{ $level->name }}</span>
                                    <span class="text-[10px] {{ $isMarkup ? 'text-error' : 'text-success' }}">
                                        @if($isMarkup)
                                            +{{ number_format(abs($disc), 2) }}% Markup
                                        @else
                                            {{ number_format($disc, 2) }}% Discount
                                        @endif
                                        {{ $priceRecord ? '(Override)' : '(Default)' }}
                                    </span>
                                </div>
                                <span class="font-bold {{ $isMarkup ? 'text-error' : 'text-primary' }} text-sm">₹{{ number_format($calculatedSellingPrice, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-admin.card>

                <!-- Unit setup -->
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30 select-none">
                        <span class="material-symbols-outlined text-primary">straighten</span>
                        <h3 class="font-title-md text-primary">Units Configuration</h3>
                    </x-slot:header>

                    @php
                        $l1 = $product->units->where('level', 1)->first();
                        $l2 = $product->units->where('level', 2)->first();
                    @endphp

                    <div class="space-y-md select-none">
                        @if($l1)
                            <div>
                                <span class="text-xs text-on-surface-variant block">Primary Unit (Level 1)</span>
                                <span class="font-bold text-primary">{{ $l1->name }} ({{ $l1->short_code }})</span>
                            </div>
                        @endif

                        @if($l2)
                            <div class="border-t border-outline-variant/20 pt-md space-y-xs">
                                <div>
                                    <span class="text-xs text-on-surface-variant block">Secondary Unit (Level 2)</span>
                                    <span class="font-bold text-primary">{{ $l2->name }} ({{ $l2->short_code }})</span>
                                </div>
                                <div>
                                    <span class="text-xs text-on-surface-variant block">Conversion Rule</span>
                                    <span class="font-medium text-secondary text-xs">1 {{ $l2->name }} = {{ (float)$l2->conversion_to_base }} {{ $l1->short_code }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-on-surface-variant block">Secondary Unit price</span>
                                    <span class="font-bold text-primary text-sm">₹{{ number_format($product->base_price * (float)$l2->conversion_to_base, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-admin.card>
            </div>
        </div>
    @else
        <div class="p-xl text-center select-none">
            <h3 class="text-error font-title-lg">Product not found.</h3>
            <a href="{{ route('admin.products.index') }}" class="text-secondary hover:underline">Back to product catalog</a>
        </div>
    @endif
</div>
