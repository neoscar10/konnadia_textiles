<x-admin.layout title="Access Restricted">
    <div class="min-h-[75vh] flex flex-col items-center justify-center text-center p-xl relative select-none">
        
        <!-- Premium Animations -->
        <style>
            @keyframes pulse-ring {
                0% { transform: scale(0.85); opacity: 0.3; }
                50% { transform: scale(1.05); opacity: 0.1; }
                100% { transform: scale(0.85); opacity: 0.3; }
            }
            @keyframes float-shield {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-8px) rotate(-1.5deg); }
            }
            @keyframes warning-blink {
                0%, 100% { opacity: 0.6; }
                50% { opacity: 1; }
            }
            .anim-pulse-ring {
                animation: pulse-ring 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            .anim-float-shield {
                animation: float-shield 3.5s ease-in-out infinite;
            }
            .anim-warning-blink {
                animation: warning-blink 2s ease-in-out infinite;
            }
        </style>

        <!-- Dynamic Glow & Rings -->
        <div class="relative flex items-center justify-center mb-xl shrink-0 select-none" style="width: 192px; height: 192px;">
            <!-- Pulsing outer circle glow -->
            <div class="absolute inset-0 rounded-full bg-error/10 border-2 border-error/20 anim-pulse-ring" style="width: 192px; height: 192px; border-radius: 9999px;"></div>
            <!-- Glow core background -->
            <div class="absolute rounded-full bg-error/5 blur-md" style="width: 112px; height: 112px; border-radius: 9999px;"></div>
            
            <!-- Circular padlock icon container -->
            <div class="relative bg-error/15 border border-error/30 shadow-inner flex items-center justify-center anim-float-shield shrink-0" style="width: 130px; height: 130px; border-radius: 9999px;">
                <span class="material-symbols-outlined text-[82px] text-error" style="font-variation-settings: 'FILL' 1, 'wght' 500;">shield_lock</span>
            </div>
            
            <!-- Warning indicator dot -->
            <div class="absolute bottom-4 right-4 w-5 h-5 bg-error rounded-full border border-white flex items-center justify-center shadow anim-warning-blink select-none" style="width: 20px; height: 20px; border-radius: 9999px;">
                <span class="text-[10px] text-white font-bold select-none">!</span>
            </div>
        </div>

        <!-- Typography & Content -->
        <div class="space-y-md max-w-xl">
            <h1 class="font-headline-lg text-primary tracking-tight text-3xl font-extrabold">Access Restricted</h1>
            <p class="font-body-lg text-on-surface-variant leading-relaxed text-sm max-w-md mx-auto">
                You do not have permission to access this section of the administration panel. Each module is restricted to authorized roles.
            </p>
            
            <div class="p-lg bg-surface-container-low/60 border border-outline-variant/30 rounded-xl mt-lg max-w-md mx-auto">
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    If you believe you should have access to this page, please contact the <strong class="text-primary font-bold">Super Admin</strong> to request permission.
                </p>
            </div>
        </div>
    </div>
</x-admin.layout>
