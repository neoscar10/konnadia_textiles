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
    <div class="flex-1 overflow-y-auto py-lg flex flex-col gap-sm admin-sidebar-scroll">
        <!-- Overview Group -->
        <div class="px-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Overview</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="dashboard">dashboard</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Dashboard</span>
            </a>
        </nav>

        <!-- Customers Group -->
        <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Customers</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm">
            <a href="{{ route('admin.customers.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.customers.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="group">group</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Customers</span>
            </a>
            <a href="{{ route('admin.customer-levels.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.customer-levels.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="stars">stars</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Customer Levels</span>
            </a>
        </nav>

        <!-- Catalog Group -->
        <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Catalog</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm">
            <a href="{{ route('admin.products.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="inventory_2">inventory_2</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Products</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="category">category</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Categories</span>
            </a>

            <a href="{{ route('admin.inventory.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="warehouse">warehouse</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Inventory</span>
            </a>
        </nav>

        <!-- Orders Group -->
        <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">Orders</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm">
            <a href="{{ route('admin.orders.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.orders.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="shopping_cart">shopping_cart</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Orders</span>
            </a>
        </nav>

        <!-- CMS Group -->
        <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">CMS</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm">
            <a href="{{ route('admin.home-content.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.home-content.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="dashboard_customize">dashboard_customize</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Home Content</span>
            </a>
        </nav>

        <!-- System Group -->
        <div class="px-md mt-md mb-xs" x-show="sidebarOpen">
            <p class="font-label-md text-on-primary/50 uppercase tracking-wider text-[10px]">System</p>
        </div>
        <nav class="flex flex-col gap-xs px-sm pb-xl">
            <a href="{{ route('admin.settings.index') }}" wire:navigate class="flex items-center rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-primary-container text-on-primary font-title-md shadow-sm' : 'text-on-primary/70 hover:text-on-primary hover:bg-primary-container/30' }}" :class="sidebarOpen ? 'gap-md px-md py-sm' : 'justify-center p-sm mx-xs'">
                <span class="material-symbols-outlined shrink-0" data-icon="settings">settings</span>
                <span class="font-label-md text-label-md" x-show="sidebarOpen">Settings</span>
            </a>
        </nav>

        <!-- Footer Section -->
        <div class="mt-auto pt-lg pb-md border-t border-on-primary/10 px-md transition-all duration-300" :class="sidebarOpen ? 'px-md' : 'px-xs'">
            <div class="space-y-xs">
                <form method="POST" action="#">
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
