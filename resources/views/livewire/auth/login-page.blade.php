<div>
    <x-slot:title>Login | Kannodia Textiles Portal</x-slot:title>
    <div class="flex-1 flex flex-col md:flex-row min-h-screen">
        <!-- Left Panel: Brand / Image Section (55%) -->
        <section class="hidden md:flex w-[55%] bg-primary relative flex-col justify-between p-xl overflow-hidden">
            <!-- Decorative Background Element -->
            <div class="absolute inset-0 z-0 opacity-10">
                <div class="absolute -top-1/4 -right-1/4 w-[800px] h-[800px] rounded-full border-[100px] border-primary-fixed"></div>
                <div class="absolute -bottom-1/4 -left-1/4 w-[600px] h-[600px] rounded-full border-[80px] border-secondary"></div>
            </div>

            <div class="relative z-10 flex items-center gap-md">
                <div class="w-12 h-12 bg-surface-container-lowest flex items-center justify-center rounded-lg shadow-md">
                    <span class="material-symbols-outlined text-primary text-[28px]" style="font-variation-settings: 'FILL' 1;">texture</span>
                </div>
                <h1 class="font-display-lg text-on-primary tracking-tight text-3xl">Kannodia Textiles</h1>
            </div>

            <div class="relative z-10 max-w-lg mb-xl">
                <div class="inline-flex items-center gap-sm px-sm py-xs bg-primary-container border border-primary-fixed/20 text-primary-fixed-dim rounded-full font-label-md mb-lg">
                    <span class="material-symbols-outlined text-[14px]">stars</span>
                    Enterprise Portal
                </div>
                <h2 class="font-headline-lg text-on-primary mb-md">Seamless Wholesale Distribution.</h2>
                <p class="font-body-lg text-primary-fixed-dim/80">Manage your product catalog, dynamic pricing, and credit-based order workflows all in one secure platform.</p>
            </div>

            <div class="relative z-10 flex items-center gap-md text-white/40">
                <div class="flex -space-x-2">
                    <img class="w-8 h-8 rounded-full border-2 border-primary object-cover" src="https://ui-avatars.com/api/?name=Admin&background=0F2744&color=fff"/>
                    <img class="w-8 h-8 rounded-full border-2 border-primary object-cover" src="https://ui-avatars.com/api/?name=User&background=0F2744&color=fff"/>
                    <div class="w-8 h-8 rounded-full border-2 border-primary bg-secondary flex items-center justify-center text-[10px] font-bold text-primary">+1k</div>
                </div>
                <p class="font-label-md">Join over 1,000 distribution partners worldwide.</p>
            </div>
        </section>

        <!-- Right Panel: Login Card (45%) -->
        <section class="w-full md:w-[45%] h-full min-h-screen flex items-center justify-center bg-surface p-xl relative">
            <div class="w-full max-w-md">
                <!-- Mobile Branding (Hidden on Desktop) -->
                <div class="md:hidden flex flex-col items-center mb-xl text-center">
                    <div class="w-16 h-16 bg-primary flex items-center justify-center rounded-xl mb-md">
                        <span class="material-symbols-outlined text-secondary text-[36px]" style="font-variation-settings: 'FILL' 1;">texture</span>
                    </div>
                    <h2 class="font-headline-md text-primary">KANNODIA TEXTILES</h2>
                </div>

                <!-- Login Card -->
                <div class="bg-surface-container-lowest rounded-xl p-xl shadow-sm border border-outline-variant/30 card-shadow">
                    <div class="mb-lg">
                        <h2 class="font-headline-md text-on-surface mb-xs">Welcome Back</h2>
                        <p class="font-body-md text-on-surface-variant">Sign in to access your portal</p>
                    </div>

                    <!-- Login Mode Tabs -->
                    <div class="flex border-b border-outline-variant/30 mb-lg">
                        <button type="button" 
                                wire:click="setLoginMode('password')" 
                                class="flex-1 pb-sm font-title-md text-center transition-all border-b-2 {{ $loginMode === 'password' ? 'border-secondary text-secondary font-bold' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface' }}">
                            Password Login
                        </button>
                        <button type="button" 
                                wire:click="setLoginMode('otp')" 
                                class="flex-1 pb-sm font-title-md text-center transition-all border-b-2 {{ $loginMode === 'otp' ? 'border-secondary text-secondary font-bold' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface' }}">
                            OTP Login
                        </button>
                    </div>

                    <!-- PASSWORD LOGIN MODE -->
                    @if($loginMode === 'password')
                        <form class="space-y-lg" wire:submit.prevent="loginWithPassword">
                            @if ($errors->any())
                                <div class="bg-error-container/20 text-error p-sm rounded-lg text-sm border border-error/30 font-body-md">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <!-- Email Field -->
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant flex items-center gap-xs" for="email">
                                    <span class="material-symbols-outlined text-[18px]">mail</span>
                                    Email Address
                                </label>
                                <div class="relative group">
                                    <input wire:model.defer="email" class="w-full h-[48px] px-md rounded-lg border border-outline-variant bg-surface-container-low focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all outline-none text-on-surface" id="email" type="email" placeholder="e.g. admin@kannodiatextiles.com" required/>
                                </div>
                                @error('email')
                                    <span class="text-error text-xs font-body-md block mt-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Password Field -->
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant flex items-center gap-xs" for="password">
                                    <span class="material-symbols-outlined text-[18px]">lock</span>
                                    Password
                                </label>
                                <div class="relative group" x-data="{ show: false }">
                                    <input wire:model.defer="password" :type="show ? 'text' : 'password'" class="w-full h-[48px] px-md rounded-lg border border-outline-variant bg-surface-container-low focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all outline-none text-on-surface pr-12" id="password" placeholder="••••••••" required/>
                                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-secondary transition-colors">
                                        <span class="material-symbols-outlined" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="text-error text-xs font-body-md block mt-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between py-xs">
                                <label class="flex items-center gap-sm cursor-pointer group">
                                    <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer"/>
                                    <span class="font-body-md text-on-surface-variant group-hover:text-on-surface transition-colors">Remember Me</span>
                                </label>
                                <a href="#" class="font-title-md text-secondary hover:underline transition-all">Forgot Password?</a>
                            </div>

                            <!-- Login Button -->
                            <button type="submit" class="w-full h-[48px] bg-secondary text-primary font-button rounded-lg shadow-md hover:bg-secondary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-sm group">
                                <span>Sign In</span>
                                <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
                            </button>
                        </form>
                    @endif

                    <!-- OTP LOGIN MODE -->
                    @if($loginMode === 'otp')
                        @if(!$otpSent)
                            <!-- Step 1: Send OTP -->
                            <form class="space-y-lg" wire:submit.prevent="requestOtp">
                                @if ($errors->any())
                                    <div class="bg-error-container/20 text-error p-sm rounded-lg text-sm border border-error/30 font-body-md">
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <div class="space-y-xs">
                                    <label class="font-label-md text-on-surface-variant flex items-center gap-xs" for="otpLogin">
                                        <span class="material-symbols-outlined text-[18px]">phone_iphone</span>
                                        Email or Mobile Number
                                    </label>
                                    <div class="relative group">
                                        <input wire:model.defer="otpLogin" class="w-full h-[48px] px-md rounded-lg border border-outline-variant bg-surface-container-low focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all outline-none text-on-surface" id="otpLogin" type="text" placeholder="e.g. customer@example.com or 9876543210" required/>
                                    </div>
                                    @error('otpLogin')
                                        <span class="text-error text-xs font-body-md block mt-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <button type="submit" class="w-full h-[48px] bg-secondary text-primary font-button rounded-lg shadow-md hover:bg-secondary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-sm group">
                                    <span>Send OTP Code</span>
                                    <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">sms</span>
                                </button>
                            </form>
                        @else
                            <!-- Step 2: Verify OTP -->
                            <form class="space-y-lg" wire:submit.prevent="loginWithOtp">
                                @if ($errors->any())
                                    <div class="bg-error-container/20 text-error p-sm rounded-lg text-sm border border-error/30 font-body-md">
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <div class="bg-secondary-container/30 text-secondary border border-secondary/20 p-sm rounded-lg text-sm font-body-md flex items-start gap-xs">
                                    <span class="material-symbols-outlined text-[18px] mt-xs">info</span>
                                    <div>
                                        An OTP has been sent for <strong>{{ $otpLogin }}</strong>.
                                        <br>
                                        <span class="text-[12px] opacity-80">Enter any 6-digit code (e.g. 123456) to proceed.</span>
                                    </div>
                                </div>

                                <div class="space-y-xs">
                                    <label class="font-label-md text-on-surface-variant flex items-center gap-xs" for="otp">
                                        <span class="material-symbols-outlined text-[18px]">pin</span>
                                        6-Digit OTP Code
                                    </label>
                                    <div class="relative group">
                                        <input wire:model.defer="otp" class="w-full h-[48px] px-md rounded-lg border border-outline-variant bg-surface-container-low focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all outline-none text-on-surface text-center tracking-widest font-bold text-lg" id="otp" type="text" maxlength="6" placeholder="••••••" required autocomplete="one-time-code"/>
                                    </div>
                                    @error('otp')
                                        <span class="text-error text-xs font-body-md block mt-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Remember Me check for OTP -->
                                <div class="flex items-center justify-between py-xs">
                                    <label class="flex items-center gap-sm cursor-pointer group">
                                        <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer"/>
                                        <span class="font-body-md text-on-surface-variant group-hover:text-on-surface transition-colors">Keep me signed in</span>
                                    </label>
                                    <button type="button" wire:click="resetOtp" class="font-title-md text-secondary hover:underline transition-all flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px]">edit</span> Change Details
                                    </button>
                                </div>

                                <button type="submit" class="w-full h-[48px] bg-secondary text-primary font-button rounded-lg shadow-md hover:bg-secondary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-sm group">
                                    <span>Verify & Sign In</span>
                                    <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">login</span>
                                </button>
                            </form>
                        @endif
                    @endif

                    <!-- Divider -->
                    <div class="my-xl flex items-center gap-md">
                        <div class="h-[1px] flex-1 bg-outline-variant/30"></div>
                        <span class="text-label-md text-outline uppercase tracking-widest">Support</span>
                        <div class="h-[1px] flex-1 bg-outline-variant/30"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-md">
                        <button class="flex items-center justify-center gap-sm p-sm border border-outline-variant rounded-lg hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-outline">help</span>
                            <span class="font-label-md text-on-surface-variant">Help Center</span>
                        </button>
                        <a href="{{ route('home') }}" class="flex items-center justify-center gap-sm p-sm border border-outline-variant rounded-lg hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-outline">home</span>
                            <span class="font-label-md text-on-surface-variant">Return Home</span>
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="mt-xl flex flex-col items-center gap-sm">
                    <div class="flex gap-md">
                        <a href="#" class="text-label-md text-outline hover:text-secondary transition-colors">Privacy Policy</a>
                        <span class="text-outline-variant">•</span>
                        <a href="#" class="text-label-md text-outline hover:text-secondary transition-colors">Terms of Service</a>
                    </div>
                    <p class="text-label-md text-outline/60">Version 1.0 | © {{ date('Y') }} Kannodia Textiles</p>
                </footer>
            </div>
        </section>
    </div>
</div>
