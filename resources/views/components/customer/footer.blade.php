<footer class="hidden lg:block w-full bg-[#001229] border-t border-slate-800 text-slate-400 py-12 mt-12">
    <div class="max-w-[1440px] mx-auto px-8 grid grid-cols-4 gap-8">
        <!-- Brand Col -->
        <div>
            <div class="flex items-center mb-4 bg-white p-2 rounded-xl w-fit shadow-md hover:scale-105 transition-transform duration-300">
                <img src="{{ asset('logo.png') }}" class="h-16 w-auto object-contain" alt="Sapnay Lifestyle Logo">
            </div>
            <p class="text-sm text-slate-400 leading-relaxed mb-6">
                Premium B2B Wholesale Textiles & Garments. Trusted manufacturer supplying quality apparel fabrics and bulk orders globally.
            </p>
            <p class="text-xs text-slate-500">
                &copy; {{ date('Y') }} Sapnay Lifestyle. All rights reserved.
            </p>
        </div>

        <!-- Quick Links -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Quick Links</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="{{ route('customer.dashboard') }}" class="hover:text-gold transition-colors">Dashboard</a></li>
                <li><a href="{{ route('customer.products.index') }}" class="hover:text-gold transition-colors">All Products</a></li>
                <li><a href="{{ route('customer.orders.index') }}" class="hover:text-gold transition-colors">My Orders</a></li>
                <li><a href="{{ route('customer.cart.index') }}" class="hover:text-gold transition-colors">Carts</a></li>
            </ul>
        </div>

        <!-- Support -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Support & Help</h4>
            <ul class="space-y-3 text-sm">
                <li><a href="{{ route('customer.privacy-policy') }}" class="hover:text-gold transition-colors">Privacy Policy</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">Minimum Order Quantities (MOQ)</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">Shipping & Logistics</a></li>
                <li><a href="{{ route('customer.contact-us') }}" class="hover:text-gold transition-colors">Contact Us</a></li>
                <li><a href="#" class="hover:text-gold transition-colors">FAQs</a></li>
            </ul>
        </div>

        <!-- Business Details -->
        <div>
            <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider font-bold tracking-tight">
                SAPNAY LIFESTYLE PVT LTD.
            </h4>
            <p class="text-sm leading-relaxed mb-4">
                Premium B2B Wholesale Textiles & Garments.
            </p>
        </div>
    </div>
</footer>
