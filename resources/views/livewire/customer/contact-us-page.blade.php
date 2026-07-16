<div>
    <!-- Page Header with Modern Gradient -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#001229] to-[#0d2a4a] text-white p-8 md:p-12 mb-8 shadow-lg">
        <div class="relative z-10 max-w-2xl">
            <span class="text-xs font-semibold text-gold uppercase tracking-widest">Get In Touch</span>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight mt-2 text-white">Contact Us</h1>
            <p class="text-sm md:text-base text-slate-300 mt-2">Have a question about our products, bulk pricing, or shipping? Feel free to reach out to us directly or fill out the message form.</p>
        </div>
        <!-- Decorative subtle background circle -->
        <div class="absolute right-0 bottom-0 w-64 h-64 bg-gold/5 rounded-full blur-3xl -mr-16 -mb-16"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Contact Details Card (5 Columns) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-ambient p-6 md:p-8 space-y-8">
                <div>
                    <h2 class="text-xl font-extrabold text-[#001229] tracking-tight">SAPNAY LIFESTYLE PVT LTD.</h2>
                    <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider">Head Office Information</p>
                </div>

                <!-- Info Blocks -->
                <div class="space-y-6">
                    <!-- Address Block -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">location_on</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Head Office Address</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                SAPNAY LIFESTYLE PVT LTD.<br>
                                "SAPNAY HOUSE"<br>
                                BAL KRISHNA SAHAY LANE,<br>
                                SHRADHANAND ROAD,<br>
                                RANCHI-834001
                            </p>
                        </div>
                    </div>

                    <!-- Phone Block -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">phone_in_talk</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Phone Number</h4>
                            <p class="text-xs text-slate-500 mt-1">
                                <a href="tel:+919934313159" class="hover:text-gold transition-colors font-semibold font-mono">+91 9934313159</a>
                            </p>
                        </div>
                    </div>

                    <!-- Email Block -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">mail</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Email Address</h4>
                            <p class="text-xs text-slate-500 mt-1">
                                <a href="mailto:sapnayfurnishings@gmail.com" class="hover:text-gold transition-colors font-semibold">sapnayfurnishings@gmail.com</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Message Form (7 Columns) -->
        <div class="lg:col-span-7">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-ambient p-6 md:p-8">
                <h2 class="text-xl font-extrabold text-[#001229] tracking-tight mb-6">Send Us a Message</h2>
                
                <form wire:submit.prevent="sendMessage" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[#001229] uppercase tracking-wider block">Your Name</label>
                            <input type="text" wire:model="name" class="w-full px-4 py-2.5 bg-slate-50 border border-outline-variant/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold text-xs text-[#001229]">
                            @error('name') <span class="text-error text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[#001229] uppercase tracking-wider block">Your Email</label>
                            <input type="email" wire:model="email" class="w-full px-4 py-2.5 bg-slate-50 border border-outline-variant/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold text-xs text-[#001229]">
                            @error('email') <span class="text-error text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[#001229] uppercase tracking-wider block">Subject</label>
                        <input type="text" wire:model="subject" class="w-full px-4 py-2.5 bg-slate-50 border border-outline-variant/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold text-xs text-[#001229]">
                        @error('subject') <span class="text-error text-[10px] font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[#001229] uppercase tracking-wider block">Message</label>
                        <textarea wire:model="message" rows="5" class="w-full px-4 py-2.5 bg-slate-50 border border-outline-variant/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold text-xs text-[#001229] resize-none" placeholder="How can we help you?"></textarea>
                        @error('message') <span class="text-error text-[10px] font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-[#001229] hover:bg-slate-800 text-white font-bold text-xs tracking-wider uppercase transition-all shadow-md">
                            <span class="material-symbols-outlined text-sm">send</span> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
