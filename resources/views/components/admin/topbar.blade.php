@props(['title' => 'Dashboard'])

<header class="sticky top-0 h-14 w-full bg-surface/80 backdrop-blur-md z-40 px-gutter flex justify-between items-center border-b border-outline-variant/30">
    <div class="flex items-center gap-xl">
        <h2 class="font-headline-md text-primary truncate max-w-xs">{{ $title }}</h2>
        
        <!-- Search Bar -->
        <div class="relative w-64 md:w-80 hidden sm:block">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant/50" data-icon="search">search</span>
            <input type="text" placeholder="Search orders, products..." class="w-full pl-xl pr-md py-sm bg-surface-container-low border-none rounded-lg font-body-md focus:ring-2 focus:ring-secondary transition-all text-on-surface">
        </div>
    </div>

    <div class="flex items-center gap-sm md:gap-lg">
        <button class="p-sm text-on-surface-variant hover:text-primary transition-colors relative">
            <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
            <span class="absolute top-1 right-1 w-2 h-2 bg-error rounded-full"></span>
        </button>
        
        <button class="p-sm text-on-surface-variant hover:text-primary transition-colors hidden sm:block">
            <span class="material-symbols-outlined" data-icon="chat_bubble">chat_bubble</span>
        </button>
        
        <div class="flex items-center gap-md pl-md border-l border-outline-variant/30 ml-sm">
            <div class="text-right hidden md:block">
                <p class="font-title-md text-sm text-primary">{{ auth()->user()->name ?? 'Admin User' }}</p>
                <p class="font-label-md text-[10px] text-on-surface-variant">{{ auth()->user()?->roles->first()?->name ?? 'Administrator' }}</p>
            </div>
            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=0F2744&color=fff" alt="Profile" class="w-10 h-10 rounded-full border-2 border-surface-container-high object-cover">
        </div>
    </div>
</header>
