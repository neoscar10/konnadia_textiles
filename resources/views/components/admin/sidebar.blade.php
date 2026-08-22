<aside class="bg-primary text-on-primary h-screen fixed left-0 top-0 flex flex-col z-50 transition-all duration-300" :class="sidebarOpen ? 'w-[260px]' : 'w-[70px]'" x-cloak>
    <!-- Brand / Logo Area -->
    <div class="h-16 flex items-center px-lg border-b border-on-primary/10 relative">
        <div class="w-8 h-8 bg-surface-container-lowest flex items-center justify-center rounded shadow-sm mr-sm shrink-0">
            <span class="material-symbols-outlined text-primary text-[20px]" style="font-variation-settings: 'FILL' 1;">texture</span>
        </div>
        <h1 class="font-title-lg tracking-tight transition-all duration-200" x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-x-2" x-transition:enter-end="opacity-100 transform translate-x-0">Kannodia Textiles</h1>
        
        <!-- Toggle Button -->
        <button @click="sidebarOpen = !sidebarOpen" class="absolute -right-4 top-1/2 -translate-y-1/2 w-8 h-8 bg-white border border-outline-variant/60 text-primary hover:text-secondary hover:bg-surface-container-lowest rounded-full flex items-center justify-center shadow-md hover:shadow-lg z-50 transition-all duration-300 hover:scale-110 active:scale-95 focus:outline-none">
            <span class="material-symbols-outlined text-[18px] font-bold inline-block transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'">chevron_left</span>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto py-lg flex flex-col gap-sm admin-sidebar-scroll"
         x-data="{
             scrollToActive() {
                 this.$nextTick(() => {
                     setTimeout(() => {
                         const activeLink = this.$el.querySelector('a.is-active-link');
                         if (activeLink) {
                             activeLink.scrollIntoView({ block: 'nearest', behavior: 'auto' });
                         }
                     }, 100);
                 });
             }
         }"
         x-init="scrollToActive()"
         x-on:livewire:navigated.window="scrollToActive()">
        <!-- Overview Group -->
        @can('access dashboard')
            <div class="px-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Overview</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                    <span class="material-symbols-outlined shrink-0" data-icon="dashboard">dashboard</span>
                    <span class="font-label-md text-label-md" x-show="sidebarOpen">Dashboard</span>
                </a>
                <a href="{{ route('admin.contact-messages.index') }}" wire:navigate class="flex items-center justify-between rounded-lg transition-all duration-200 {{ request()->routeIs('admin.contact-messages.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs relative'">
                    <div class="flex items-center gap-md">
                        <span class="material-symbols-outlined shrink-0" data-icon="mail">mail</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Contact Messages</span>
                    </div>
                    @php
                        $unreadMessagesCount = \App\Models\ContactMessage::where('is_read', false)->count();
                    @endphp
                    @if($unreadMessagesCount > 0)
                        <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0" x-show="sidebarOpen">
                            {{ $unreadMessagesCount }}
                        </span>
                        <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border border-primary" x-show="!sidebarOpen"></span>
                    @endif
                </a>
            </nav>
        @endcan

        <!-- Customers Group -->
        @if(auth()->user()->can('access customers') || auth()->user()->can('access customer-levels'))
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Customers</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                @can('access customers')
                    <a href="{{ route('admin.customers.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.customers.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="group">group</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Customers</span>
                    </a>
                @endcan
                @can('access customer-levels')
                    <a href="{{ route('admin.customer-levels.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.customer-levels.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="stars">stars</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Customer Levels</span>
                    </a>
                @endcan
            </nav>
        @endif

        <!-- Catalog Group -->
        @if(auth()->user()->can('access products') || auth()->user()->can('access design-catalog') || auth()->user()->can('access categories') || auth()->user()->can('access tags') || auth()->user()->can('access inventory') || auth()->user()->can('access retail-shops') || auth()->user()->can('access product-transfers'))
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Catalog</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                @can('access products')
                    <a href="{{ route('admin.products.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="inventory_2">inventory_2</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Products</span>
                    </a>
                @endcan
                @can('access design-catalog')
                    <a href="{{ route('admin.design-catalog.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.design-catalog.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="collections">collections</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Design Catalog</span>
                    </a>
                @endcan
                @can('access categories')
                    <a href="{{ route('admin.categories.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="category">category</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Categories</span>
                    </a>
                @endcan
                @can('access tags')
                    <a href="{{ route('admin.tags.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.tags.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="sell">sell</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Tags</span>
                    </a>
                @endcan
                @can('access inventory')
                    <a href="{{ route('admin.inventory.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="warehouse">warehouse</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Inventory</span>
                    </a>
                @endcan
                @can('access retail-shops')
                    <a href="{{ route('admin.retail-shops.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.retail-shops.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="storefront">storefront</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Retail Shops</span>
                    </a>
                @endcan
                @can('access product-transfers')
                    <a href="{{ route('admin.product-transfers.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.product-transfers.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="sync_alt">sync_alt</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Product Transfers</span>
                    </a>
                @endcan
            </nav>
        @endif

        <!-- Orders Group -->
        @can('access orders')
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Orders</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                <a href="{{ route('admin.orders.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.orders.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                    <span class="material-symbols-outlined shrink-0" data-icon="shopping_cart">shopping_cart</span>
                    <span class="font-label-md text-label-md" x-show="sidebarOpen">Orders</span>
                </a>
            </nav>
        @endcan

        <!-- CMS Group -->
        @can('access home-content')
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">CMS</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                <a href="{{ route('admin.home-content.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.home-content.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                    <span class="material-symbols-outlined shrink-0" data-icon="dashboard_customize">dashboard_customize</span>
                    <span class="font-label-md text-label-md" x-show="sidebarOpen">Home Content</span>
                </a>
            </nav>
        @endcan

        <!-- Factory Group -->
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) || auth()->user()->can('manage_labor'))
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Factory</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm">
                
                <!-- Production Jobs (Standalone) -->
                <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.production.jobs.*') || request()->routeIs('admin.production.batches.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                    <span class="material-symbols-outlined shrink-0" data-icon="precision_manufacturing">precision_manufacturing</span>
                    <span class="font-label-md text-label-md" x-show="sidebarOpen">Production Jobs</span>
                </a>

                <!-- Task Master (Standalone) -->
                <a href="{{ route('factory.tasks.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('factory.tasks.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                    <span class="material-symbols-outlined shrink-0" data-icon="task_alt">task_alt</span>
                    <span class="font-label-md text-label-md" x-show="sidebarOpen">Task Master</span>
                </a>

                <!-- Manufacturing Prod Group -->
                <div x-data="{ open: {{ request()->routeIs('factory.products.*') || request()->routeIs('admin.production.product-categories.*') ? 'true' : 'false' }} }" class="flex flex-col">
                    <button @click="open = !open; if(!sidebarOpen && open) sidebarOpen = true;" type="button" class="flex items-center justify-between rounded-lg transition-all duration-200 text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30 w-full" :class="sidebarOpen ? 'px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <div class="flex items-center gap-md">
                            <span class="material-symbols-outlined shrink-0" data-icon="inventory_2">inventory_2</span>
                            <span class="font-label-md text-label-md" x-show="sidebarOpen">Manufacturing Prod</span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
                    </button>
                    <div x-show="open && sidebarOpen" x-collapse x-cloak>
                        <div class="flex flex-col gap-1 pl-11 pr-2 py-1 relative before:absolute before:left-[22px] before:top-0 before:bottom-0 before:w-px before:bg-on-primary/10">
                            <a href="{{ route('factory.products.index') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('factory.products.*') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Products
                            </a>
                            <a href="{{ route('admin.production.product-categories.index') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.production.product-categories.*') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Categories
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Raw Materials Group -->
                <div x-data="{ open: {{ request()->routeIs('factory.raw-materials.*') || request()->routeIs('admin.units.*') || request()->routeIs('factory.units.*') ? 'true' : 'false' }} }" class="flex flex-col">
                    <button @click="open = !open; if(!sidebarOpen && open) sidebarOpen = true;" type="button" class="flex items-center justify-between rounded-lg transition-all duration-200 text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30 w-full" :class="sidebarOpen ? 'px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <div class="flex items-center gap-md">
                            <span class="material-symbols-outlined shrink-0" data-icon="deployed_code">deployed_code</span>
                            <span class="font-label-md text-label-md" x-show="sidebarOpen">Raw Materials</span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
                    </button>
                    <div x-show="open && sidebarOpen" x-collapse x-cloak>
                        <div class="flex flex-col gap-1 pl-11 pr-2 py-1 relative before:absolute before:left-[22px] before:top-0 before:bottom-0 before:w-px before:bg-on-primary/10">
                            <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('factory.raw-materials.index') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Directory
                            </a>
                            <a href="{{ route('factory.raw-materials.purchase') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('factory.raw-materials.purchase') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Purchase Entry
                            </a>
                            <a href="{{ route('factory.raw-materials.batches') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('factory.raw-materials.batches') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Inventory Batches
                            </a>
                            <a href="{{ route('admin.units.index') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.units.*') || request()->routeIs('factory.units.*') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Units Management
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Labor & Payroll Group -->
                <div x-data="{ open: {{ request()->routeIs('admin.labor.*') || request()->routeIs('admin.production.tracking-history') ? 'true' : 'false' }} }" class="flex flex-col">
                    <button @click="open = !open; if(!sidebarOpen && open) sidebarOpen = true;" type="button" class="flex items-center justify-between rounded-lg transition-all duration-200 text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30 w-full" :class="sidebarOpen ? 'px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <div class="flex items-center gap-md">
                            <span class="material-symbols-outlined shrink-0" data-icon="engineering">engineering</span>
                            <span class="font-label-md text-label-md" x-show="sidebarOpen">Labor & Payroll</span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
                    </button>
                    <div x-show="open && sidebarOpen" x-collapse x-cloak>
                        <div class="flex flex-col gap-1 pl-11 pr-2 py-1 relative before:absolute before:left-[22px] before:top-0 before:bottom-0 before:w-px before:bg-on-primary/10">
                            <a href="{{ route('admin.labor.index') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.labor.*') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Directory & Wages
                            </a>
                            <a href="{{ route('admin.production.tracking-history') }}" wire:navigate class="px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.production.tracking-history') ? 'is-active-link bg-primary-container/40 text-on-primary font-bold' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/20' }}">
                                Tracking History
                            </a>
                        </div>
                    </div>
                </div>

            </nav>
        @endif

        <!-- System Group -->
        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('access settings'))
            <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
                <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">System</p>
            </div>
            <nav class="flex flex-col gap-xs px-sm pb-xl">
                @if(auth()->user()->hasRole('super_admin'))
                    <a href="{{ route('admin.admins.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.admins.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="manage_accounts">manage_accounts</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Admins</span>
                    </a>
                @endif
                @can('access settings')
                    <a href="{{ route('admin.settings.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'is-active-link bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined shrink-0" data-icon="settings">settings</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Settings</span>
                    </a>
                @endcan
            </nav>
        @endif

        <!-- Footer Section -->
        <div class="mt-auto pt-lg pb-md border-t border-on-primary/10 px-md transition-all duration-300" :class="sidebarOpen ? 'px-md' : 'px-xs'">
            <div class="space-y-xs">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center text-on-primary/70 hover:text-on-primary hover:bg-error/20 rounded-lg transition-colors" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                        <span class="material-symbols-outlined text-error-container shrink-0" data-icon="logout">logout</span>
                        <span class="font-label-md text-label-md" x-show="sidebarOpen">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
