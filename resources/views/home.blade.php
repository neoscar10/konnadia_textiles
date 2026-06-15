<x-layouts.public title="Home | Kannodia Textiles">
    <!-- Navbar -->
    <header class="w-full bg-surface-container-lowest/80 backdrop-blur-md sticky top-0 z-50 border-b border-outline-variant/30">
        <div class="max-w-[1440px] mx-auto px-gutter py-sm flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex items-center gap-md">
                <div class="w-12 h-12 bg-primary flex items-center justify-center rounded-lg">
                    <span class="material-symbols-outlined text-secondary text-[24px]" style="font-variation-settings: 'FILL' 1;">texture</span>
                </div>
                <h1 class="font-headline-md text-primary tracking-tight">Kannodia Textiles</h1>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center gap-xl">
                <a href="#" class="font-button text-on-surface hover:text-primary transition-colors">Platform</a>
                <a href="#" class="font-button text-on-surface hover:text-primary transition-colors">Catalog</a>
                <a href="#" class="font-button text-on-surface hover:text-primary transition-colors">Credit Options</a>
                <a href="#" class="font-button text-on-surface hover:text-primary transition-colors">Contact</a>
            </nav>

            <!-- Login CTA -->
            <div>
                <a href="{{ route('login') }}" class="px-xl py-sm bg-primary text-on-primary font-button rounded-lg shadow-sm hover:bg-primary-container transition-all">
                    Login to Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col">
        <section class="w-full bg-background relative overflow-hidden py-24 lg:py-32">
            <!-- Decorative Background Element -->
            <div class="absolute top-0 right-0 w-1/2 h-full bg-primary-fixed-dim/20 rounded-bl-[120px] -z-10"></div>
            
            <div class="max-w-[1440px] mx-auto px-gutter grid grid-cols-1 lg:grid-cols-2 gap-xl items-center">
                <!-- Hero Content -->
                <div class="space-y-lg max-w-2xl">
                    <div class="inline-flex items-center gap-sm px-md py-xs bg-secondary-container text-on-secondary-container rounded-full font-label-md">
                        <span class="material-symbols-outlined text-[16px]">verified</span>
                        Trusted B2B Platform
                    </div>
                    <h2 class="font-display-lg text-primary leading-tight">
                        Simplifying B2B Textile <br/>
                        <span class="text-secondary">Ordering & Distribution</span>
                    </h2>
                    <p class="font-body-lg text-on-surface-variant max-w-xl text-lg">
                        Manage your wholesale catalog, track customer credit limits, streamline order approvals, and monitor inventory seamlessly from a single enterprise-grade platform.
                    </p>
                    <div class="flex items-center gap-md pt-md">
                        <a href="{{ route('login') }}" class="px-xl py-md bg-secondary text-primary font-button text-[16px] rounded-lg shadow-md hover:bg-secondary-fixed-dim transition-all flex items-center gap-sm">
                            Access Dealer Portal
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <a href="#" class="px-xl py-md bg-surface border border-outline-variant text-on-surface font-button text-[16px] rounded-lg hover:bg-surface-container transition-all">
                            Become a Partner
                        </a>
                    </div>
                </div>

                <!-- Hero Image Placeholder (Abstract Layout) -->
                <div class="relative w-full h-[500px] bg-surface-container-lowest rounded-2xl border border-outline-variant/30 card-shadow p-xl flex flex-col gap-md hidden lg:flex">
                    <!-- Fake Dashboard UI -->
                    <div class="flex justify-between items-center pb-md border-b border-outline-variant/30">
                        <div class="w-32 h-6 bg-surface-container rounded"></div>
                        <div class="w-10 h-10 bg-primary-container rounded-full"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-md">
                        <div class="h-24 bg-primary-fixed-dim/30 rounded-xl"></div>
                        <div class="h-24 bg-secondary-container/30 rounded-xl"></div>
                        <div class="h-24 bg-surface-container rounded-xl"></div>
                    </div>
                    <div class="flex-1 bg-surface-container-low rounded-xl mt-md"></div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-24 bg-surface">
            <div class="max-w-[1440px] mx-auto px-gutter text-center space-y-xl">
                <div class="space-y-sm">
                    <h3 class="font-headline-lg text-primary">Everything you need to scale distribution</h3>
                    <p class="font-body-lg text-on-surface-variant max-w-2xl mx-auto">
                        A fully integrated suite for textile manufacturers and major distributors.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-xl text-left">
                    <!-- Feature 1 -->
                    <div class="bg-surface-container-lowest p-xl rounded-2xl border border-outline-variant/30 card-shadow hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-primary-container text-primary-fixed flex items-center justify-center rounded-lg mb-lg">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <h4 class="font-title-lg text-on-surface mb-sm">Product Catalog</h4>
                        <p class="font-body-md text-on-surface-variant">Real-time inventory sync, dynamic variant tracking, and categorized bulk listing.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-surface-container-lowest p-xl rounded-2xl border border-outline-variant/30 card-shadow hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-secondary-container text-on-secondary-container flex items-center justify-center rounded-lg mb-lg">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <h4 class="font-title-lg text-on-surface mb-sm">Customer-Level Pricing</h4>
                        <p class="font-body-md text-on-surface-variant">Assign dynamic pricing tiers based on dealer volume, geography, or contract terms.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-surface-container-lowest p-xl rounded-2xl border border-outline-variant/30 card-shadow hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-error-container text-on-error-container flex items-center justify-center rounded-lg mb-lg">
                            <span class="material-symbols-outlined">credit_card</span>
                        </div>
                        <h4 class="font-title-lg text-on-surface mb-sm">Credit-Based Ordering</h4>
                        <p class="font-body-md text-on-surface-variant">Manage outstanding limits, block delinquent accounts, and route large orders for approval.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Workflow Section -->
        <section class="py-24 bg-primary text-on-primary text-center">
            <div class="max-w-[1440px] mx-auto px-gutter space-y-xl">
                <h3 class="font-headline-lg">Streamlined Wholesale Flow</h3>
                
                <div class="flex flex-col md:flex-row items-center justify-center gap-lg md:gap-xl">
                    <div class="flex flex-col items-center gap-md w-48">
                        <div class="w-16 h-16 bg-primary-container border border-primary-fixed/30 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px] text-primary-fixed-dim">storefront</span>
                        </div>
                        <p class="font-title-md">Browse Catalog</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary hidden md:block">arrow_forward</span>
                    <div class="flex flex-col items-center gap-md w-48">
                        <div class="w-16 h-16 bg-primary-container border border-primary-fixed/30 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px] text-primary-fixed-dim">shopping_cart</span>
                        </div>
                        <p class="font-title-md">Place Credit Order</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary hidden md:block">arrow_forward</span>
                    <div class="flex flex-col items-center gap-md w-48">
                        <div class="w-16 h-16 bg-secondary-container border border-secondary/30 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px] text-on-secondary-container">rule</span>
                        </div>
                        <p class="font-title-md text-secondary-fixed">Admin Approval</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary hidden md:block">arrow_forward</span>
                    <div class="flex flex-col items-center gap-md w-48">
                        <div class="w-16 h-16 bg-primary-container border border-primary-fixed/30 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px] text-primary-fixed-dim">local_shipping</span>
                        </div>
                        <p class="font-title-md">Dispatch Tracking</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-background py-xl border-t border-outline-variant/30">
        <div class="max-w-[1440px] mx-auto px-gutter flex flex-col md:flex-row justify-between items-center gap-lg">
            <div class="flex items-center gap-md">
                <div class="w-8 h-8 bg-primary flex items-center justify-center rounded">
                    <span class="material-symbols-outlined text-secondary text-[16px]" style="font-variation-settings: 'FILL' 1;">texture</span>
                </div>
                <span class="font-title-md text-primary">Kannodia Textiles</span>
            </div>
            <p class="font-body-md text-on-surface-variant">© {{ date('Y') }} Kannodia Textiles Enterprise Solutions. All rights reserved.</p>
        </div>
    </footer>
</x-layouts.public>
