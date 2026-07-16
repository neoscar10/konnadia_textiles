<x-layouts.public title="Home | Sapnay Lifestyle">
    <!-- Navbar -->
    <header class="w-full bg-[#001229]/95 backdrop-blur-md sticky top-0 z-50 border-b border-slate-800">
        <div class="max-w-[1440px] mx-auto px-6 py-4 flex justify-between items-center h-20">
            <!-- Logo -->
            <a href="#" class="flex items-center bg-white p-2 rounded-xl shadow-md transition-transform hover:scale-105 duration-300 w-fit">
                <img src="{{ asset('logo.png') }}" class="h-16 w-auto object-contain" alt="Sapnay Lifestyle Logo">
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="#platform" class="text-sm font-semibold text-slate-300 hover:text-gold transition-colors">Platform Features</a>
                <a href="#collections" class="text-sm font-semibold text-slate-300 hover:text-gold transition-colors">Collections</a>
                <a href="#workflow" class="text-sm font-semibold text-slate-300 hover:text-gold transition-colors">Wholesale Flow</a>
            </nav>

            <!-- Login CTA -->
            <div>
                <a href="{{ route('login') }}" class="px-6 py-2.5 bg-gold text-[#001229] text-sm font-bold rounded-lg shadow-md hover:bg-gold-accent transition-all duration-200">
                    Login to Portal
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 flex flex-col bg-[#faf9fc]">
        
        <!-- Hero Section -->
        <section class="w-full bg-gradient-to-b from-[#001229] to-[#0f2744] relative overflow-hidden py-20 lg:py-28 text-white">
            <!-- Decorative Subtle Grid & Gradients -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-gold/10 via-transparent to-transparent -z-10"></div>
            <div class="absolute -bottom-48 -left-48 w-96 h-96 bg-primary/20 rounded-full blur-3xl -z-10"></div>

            <div class="max-w-[1440px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Hero Content -->
                <div class="space-y-6 lg:col-span-6 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-gold/10 text-gold-accent border border-gold/20 rounded-full text-xs font-bold uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                        Enterprise Grade B2B Platform
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight leading-[1.1] text-white">
                        Modern Wholesale Ordering <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold via-gold-accent to-white">Designed for Scale.</span>
                    </h2>
                    <p class="text-slate-300 text-base lg:text-lg leading-relaxed max-w-xl">
                        Streamline your entire textile sourcing cycle. View exclusive trade collections, track custom line sheet pricing, monitor real-time credit lines, and speed up logistics approval directly from our digital portal.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        <a href="{{ route('login') }}" class="px-6 py-3.5 bg-gold text-[#001229] font-bold text-sm rounded-lg shadow-lg hover:shadow-gold/20 hover:scale-[1.02] transition-all flex items-center gap-2">
                            Access Dealer Portal
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Interactive Dashboard Mockup on Right -->
                <div class="lg:col-span-6">
                    <div class="relative w-full bg-[#001229] rounded-2xl border border-slate-800 shadow-2xl p-6 overflow-hidden">
                        <!-- Top Navbar mockup -->
                        <div class="flex justify-between items-center pb-4 border-b border-slate-800/80 mb-5">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                <span class="text-[11px] text-slate-400 font-semibold ml-2">Raj Garments &bull; Distributor Portal</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] font-bold">Active Line</span>
                            </div>
                        </div>

                        <!-- Main content grid inside mockup -->
                        <div class="space-y-4">
                            <!-- Metric cards -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-slate-900/80 border border-slate-800/60 p-3 rounded-lg">
                                    <span class="text-[9px] text-slate-400 font-semibold uppercase block">Credit Limit</span>
                                    <span class="text-sm font-bold text-white mt-1 block">₹10.0L</span>
                                </div>
                                <div class="bg-slate-900/80 border border-slate-800/60 p-3 rounded-lg">
                                    <span class="text-[9px] text-slate-400 font-semibold uppercase block">Available</span>
                                    <span class="text-sm font-bold text-gold mt-1 block">₹6.8L</span>
                                </div>
                                <div class="bg-slate-900/80 border border-slate-800/60 p-3 rounded-lg">
                                    <span class="text-[9px] text-slate-400 font-semibold uppercase block">Outstanding</span>
                                    <span class="text-sm font-bold text-slate-300 mt-1 block">₹3.2L</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800/50">
                                <div class="flex justify-between items-center mb-1 text-[10px] text-slate-400">
                                    <span>Credit Utilization</span>
                                    <span class="font-bold text-white">32% Used</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-gold h-2 rounded-full" style="width: 32%"></div>
                                </div>
                            </div>

                            <!-- Orders & Inventory Status Lists -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <!-- Active Shipment Tracking -->
                                <div class="bg-slate-900/80 border border-slate-800/60 p-3 rounded-lg space-y-2.5">
                                    <span class="text-[10px] text-slate-300 font-bold block pb-1 border-b border-slate-800/50">Live Shipments</span>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <div class="flex items-center gap-1.5 text-slate-200">
                                                <span class="material-symbols-outlined text-gold text-[12px]">local_shipping</span>
                                                <span class="font-semibold">KT-9204</span>
                                            </div>
                                            <span class="px-1.5 py-0.5 rounded bg-blue-950 text-blue-400 text-[9px] font-bold">In Transit</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px]">
                                            <div class="flex items-center gap-1.5 text-slate-200">
                                                <span class="material-symbols-outlined text-slate-400 text-[12px]">check_circle</span>
                                                <span class="font-semibold">KT-9201</span>
                                            </div>
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-400 text-[9px] font-bold">Delivered</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Catalog Inventory -->
                                <div class="bg-slate-900/80 border border-slate-800/60 p-3 rounded-lg space-y-2.5">
                                    <span class="text-[10px] text-slate-300 font-bold block pb-1 border-b border-slate-800/50">Stock Catalog Preview</span>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-200 font-medium truncate">Indigo Chambray Weave</span>
                                            <span class="text-gold font-bold">4.2k m</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-200 font-medium truncate">Crimson Premium Satin</span>
                                            <span class="text-rose-400 font-bold">Low Stock</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Textile Collections Showcase Section -->
        <section id="collections" class="py-20 bg-white border-b border-slate-100">
            <div class="max-w-[1440px] mx-auto px-6 space-y-12">
                <div class="text-center space-y-3">
                    <span class="text-xs font-bold text-gold uppercase tracking-widest">Our Offerings</span>
                    <h3 class="text-3xl font-extrabold text-[#001229] tracking-tight">Premium Wholesale Collections</h3>
                    <p class="text-slate-500 text-sm max-w-xl mx-auto">
                        Sourced globally, processed to perfection. View our core commercial-grade materials available for bulk dispatch.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Collection 1 -->
                    <div class="group bg-[#faf9fc] rounded-xl border border-slate-200/60 overflow-hidden shadow-sm hover:shadow-md transition-all">
                        <div class="h-48 bg-gradient-to-tr from-[#001229] to-[#0f2744] flex items-center justify-center relative">
                            <span class="material-symbols-outlined text-[64px] text-gold/30 group-hover:scale-110 transition-transform">texture</span>
                            <div class="absolute bottom-4 left-4 bg-[#001229]/80 backdrop-blur-md px-3 py-1 rounded border border-slate-700/60 text-[10px] font-bold text-gold uppercase">100% Organic</div>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="font-bold text-lg text-[#001229]">Premium Cotton Weaves</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Highly breathable, lightweight threads matching luxury shirting and apparel standards. Pre-shrunk and double combed.
                            </p>
                        </div>
                    </div>

                    <!-- Collection 2 -->
                    <div class="group bg-[#faf9fc] rounded-xl border border-slate-200/60 overflow-hidden shadow-sm hover:shadow-md transition-all">
                        <div class="h-48 bg-gradient-to-tr from-[#7b5900] to-[#fcca66]/40 flex items-center justify-center relative">
                            <span class="material-symbols-outlined text-[64px] text-white/30 group-hover:scale-110 transition-transform">palette</span>
                            <div class="absolute bottom-4 left-4 bg-[#001229]/80 backdrop-blur-md px-3 py-1 rounded border border-slate-700/60 text-[10px] font-bold text-gold uppercase">Silk Linens</div>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="font-bold text-lg text-[#001229]">Linen &amp; Silk Blends</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Curated textures featuring unmatched luster and luxury feel. Sourced directly from local weavers for high-end ethnic ranges.
                            </p>
                        </div>
                    </div>

                    <!-- Collection 3 -->
                    <div class="group bg-[#faf9fc] rounded-xl border border-slate-200/60 overflow-hidden shadow-sm hover:shadow-md transition-all">
                        <div class="h-48 bg-gradient-to-tr from-slate-800 to-slate-900 flex items-center justify-center relative">
                            <span class="material-symbols-outlined text-[64px] text-white/20 group-hover:scale-110 transition-transform">layers</span>
                            <div class="absolute bottom-4 left-4 bg-[#001229]/80 backdrop-blur-md px-3 py-1 rounded border border-slate-700/60 text-[10px] font-bold text-gold uppercase">Heavy Duty</div>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="font-bold text-lg text-[#001229]">Technical Synthetics</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Robust, stain-resistant polyester fabrics designed for uniforms, commercial drapery, and high-frequency hospitality usage.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features & Platform Pillars Section -->
        <section id="platform" class="py-20 bg-[#faf9fc]">
            <div class="max-w-[1440px] mx-auto px-6 space-y-12">
                <div class="text-center space-y-3">
                    <span class="text-xs font-bold text-gold uppercase tracking-widest">Designed for Enterprise</span>
                    <h3 class="text-3xl font-extrabold text-[#001229] tracking-tight">Everything You Need to Scale</h3>
                    <p class="text-slate-500 text-sm max-w-xl mx-auto">
                        Say goodbye to messy spreadsheets, endless phone tag, and opaque shipping cycles.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature Card 1 -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-ambient space-y-4 hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-gold/10 text-gold rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">payments</span>
                        </div>
                        <h4 class="text-lg font-bold text-[#001229]">Flexible Credit Facilities</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Procure immediately and settle under custom credit agreements. Track available balance, credit ceilings, and invoice statuses.
                        </p>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-ambient space-y-4 hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-gold/10 text-gold rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">sell</span>
                        </div>
                        <h4 class="text-lg font-bold text-[#001229]">Custom Partner Pricing</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Your line sheets update dynamically based on transaction volumes and level. Always see valid contract quotes.
                        </p>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-ambient space-y-4 hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 bg-gold/10 text-gold rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">local_shipping</span>
                        </div>
                        <h4 class="text-lg font-bold text-[#001229]">Real-Time Logistics</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Monitor the dispatch timeline of your bales, receive automated invoice receipts, and keep track of transit logs.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Workflow / Process Section -->
        <section id="workflow" class="py-20 bg-[#001229] text-white">
            <div class="max-w-[1440px] mx-auto px-6 space-y-12">
                <div class="text-center space-y-3">
                    <span class="text-xs font-bold text-gold uppercase tracking-widest">Efficiency Redefined</span>
                    <h3 class="text-3xl font-extrabold text-white tracking-tight">The Digital Wholesale Pipeline</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center shadow-lg relative">
                            <span class="material-symbols-outlined text-2xl text-gold">storefront</span>
                            <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-gold text-[#001229] font-black text-xs flex items-center justify-center">1</span>
                        </div>
                        <h4 class="font-bold text-sm text-slate-100">Select &amp; Cart</h4>
                        <p class="text-xs text-slate-400 max-w-[200px] leading-relaxed">Browse commercial fabrics and load cart by roll parameters.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center shadow-lg relative">
                            <span class="material-symbols-outlined text-2xl text-gold">credit_score</span>
                            <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-gold text-[#001229] font-black text-xs flex items-center justify-center">2</span>
                        </div>
                        <h4 class="font-bold text-sm text-slate-100">Credit Checkout</h4>
                        <p class="text-xs text-slate-400 max-w-[200px] leading-relaxed">Place order securely using your customized line credit terms.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center shadow-lg relative">
                            <span class="material-symbols-outlined text-2xl text-gold">rule</span>
                            <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-gold text-[#001229] font-black text-xs flex items-center justify-center">3</span>
                        </div>
                        <h4 class="font-bold text-sm text-slate-100">Admin Approval</h4>
                        <p class="text-xs text-slate-400 max-w-[200px] leading-relaxed">System instantly logs and routes request for automatic approval checks.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center shadow-lg relative">
                            <span class="material-symbols-outlined text-2xl text-gold">local_shipping</span>
                            <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-gold text-[#001229] font-black text-xs flex items-center justify-center">4</span>
                        </div>
                        <h4 class="font-bold text-sm text-slate-100">Dispatch Dispatch</h4>
                        <p class="text-xs text-slate-400 max-w-[200px] leading-relaxed">Track shipping truck allocation and arrival dates in real-time.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Become a Partner CTA -->
        <section id="onboarding" class="py-20 bg-white">
            <div class="max-w-[1000px] mx-auto px-6">
                <div class="bg-gradient-to-br from-[#001229] to-[#0f2744] rounded-3xl p-10 lg:p-14 text-white text-center space-y-6 relative overflow-hidden border border-slate-800 shadow-2xl">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-gold/5 via-transparent to-transparent -z-10"></div>
                    
                    <span class="text-xs font-bold text-gold uppercase tracking-widest">Trade Application</span>
                    <h3 class="text-3xl lg:text-4xl font-extrabold tracking-tight">Become an Authorized Dealer</h3>
                    <p class="text-slate-300 text-sm max-w-xl mx-auto leading-relaxed">
                        Interested in stocking materials from Sapnay Lifestyle? Reach out to our account acquisition desk to set up your corporate account, register your GST numbers, and get approved for a dedicated B2B credit line.
                    </p>
                    <div class="pt-4 flex flex-col sm:flex-row justify-center gap-4">
                        <a href="mailto:sapnayfurnishings@gmail.com" class="px-8 py-3.5 bg-gold text-[#001229] font-bold text-sm rounded-lg hover:bg-gold-accent hover:scale-[1.02] transition-all">
                            Contact Trade Desk
                        </a>
                        <a href="{{ route('login') }}" class="px-8 py-3.5 bg-slate-800/80 border border-slate-700 text-slate-200 font-bold text-sm rounded-lg hover:bg-slate-850 hover:text-white transition-all">
                            Portal Login
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-[#001229] py-12 border-t border-slate-800 text-slate-400">
        <div class="max-w-[1440px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center bg-white p-1.5 rounded-lg shadow-sm hover:scale-105 transition-transform duration-300 w-fit">
                <img src="{{ asset('logo.png') }}" class="h-10 w-auto object-contain" alt="Sapnay Lifestyle Logo">
            </div>
            <p class="text-xs">&copy; {{ date('Y') }} Sapnay Lifestyle. All rights reserved.</p>
        </div>
    </footer>
</x-layouts.public>
