<footer class="hidden lg:block w-full bg-[#001229] border-t border-slate-800 text-slate-400 py-12 mt-12">
    <div class="max-w-[1440px] mx-auto px-8 grid grid-cols-4 gap-8">
        <!-- Brand Col -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-gold text-2xl">storefront</span>
                <span class="font-bold text-white text-lg tracking-tight">
                    Kannodia<span class="text-gold"> Textiles</span>
                </span>
            </div>
            <p class="text-sm text-slate-400 leading-relaxed mb-6">
                Premium B2B Wholesale Textiles & Garments. Trusted manufacturer supplying quality apparel fabrics and bulk orders globally.
            </p>
            <p class="text-xs text-slate-500">
                &copy; {{ date('Y') }} Kannodia Textiles. All rights reserved.
            </p>
        </div>

        <!-- Quick Links -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Quick Links</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="{{ route('customer.dashboard') }}" class="hover:text-gold transition-colors">Dashboard</a></li>
                <li><a href="{{ route('customer.products.index') }}" class="hover:text-gold transition-colors">All Products</a></li>
                <li><a href="{{ route('customer.categories.index') }}" class="hover:text-gold transition-colors">Categories</a></li>
                <li><a href="{{ route('customer.orders.index') }}" class="hover:text-gold transition-colors">My Orders</a></li>
                <li><a href="{{ route('customer.cart.saved') }}" class="hover:text-gold transition-colors">Saved Carts</a></li>
            </ul>
        </div>

        <!-- Support -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Support & Help</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="#" class="hover:text-gold transition-colors">Wholesale Policy</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">Minimum Order Quantities (MOQ)</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">Shipping & Logistics</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">Contact Support</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">FAQs</a></li>
            </ul>
        </div>

        <!-- Business Details -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Business Office</h4>
            <p class="text-sm leading-relaxed mb-4">
                Kannodia Textiles Ltd.<br>
                Textile Zone, Sector 4,<br>
                Surat, Gujarat, India - 395003
            </p>
            <div class="flex items-center gap-2 text-sm text-white font-medium">
                <span class="material-symbols-outlined text-gold">phone_in_talk</span>
                <span>+91 98765 43210</span>
            </div>
        </div>
    </div>
</footer>
