@props([
    'status' => 'submitted'
])

@php
    $statusLower = strtolower(str_replace('_', ' ', $status));

    $steps = [
        ['key' => 'submitted', 'label' => 'Submitted', 'icon' => 'assignment_turned_in'],
        ['key' => 'under review', 'label' => 'Under Review', 'icon' => 'rate_review'],
        ['key' => 'pending approval', 'label' => 'Pending Approval', 'icon' => 'pending_actions'],
        ['key' => 'approved', 'label' => 'Approved', 'icon' => 'check_circle'],
        ['key' => 'dispatched', 'label' => 'Dispatched', 'icon' => 'local_shipping']
    ];

    // Determine current index
    $currentIndex = 0;
    if ($statusLower === 'under review' || $statusLower === 'review') {
        $currentIndex = 1;
    } elseif ($statusLower === 'pending approval') {
        $currentIndex = 2;
    } elseif ($statusLower === 'approved') {
        $currentIndex = 3;
    } elseif ($statusLower === 'dispatched' || $statusLower === 'shipped') {
        $currentIndex = 4;
    } elseif ($statusLower === 'rejected') {
        // Special case: show rejected style at step 4
        $steps[3] = ['key' => 'rejected', 'label' => 'Rejected', 'icon' => 'cancel'];
        $currentIndex = 3;
    }
@endphp

<div class="w-full py-4">
    <!-- Desktop progress bar -->
    <div class="hidden md:flex items-center justify-between w-full relative">
        <!-- Connecting Line -->
        <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-200 z-0"></div>
        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-gold z-0 transition-all duration-500" style="width: {{ ($currentIndex / (count($steps) - 1)) * 100 }}%"></div>

        <!-- Steps -->
        @foreach($steps as $index => $step)
            @php
                $isActive = $index <= $currentIndex;
                $isCurrent = $index === $currentIndex;
                $isRejected = $step['key'] === 'rejected';
            @endphp
            <div class="flex flex-col items-center z-10 relative bg-transparent px-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 {{ $isCurrent ? ($isRejected ? 'bg-rose-50 border-rose-500 text-rose-500 scale-110' : 'bg-gold border-gold text-[#001229] scale-110') : ($isActive ? 'bg-gold/10 border-gold text-gold' : 'bg-white border-slate-200 text-slate-400') }}">
                    <span class="material-symbols-outlined text-xl">{{ $step['icon'] }}</span>
                </div>
                <span class="text-xs font-bold mt-2 {{ $isCurrent ? ($isRejected ? 'text-rose-600' : 'text-[#001229]') : ($isActive ? 'text-slate-700' : 'text-slate-400') }}">{{ $step['label'] }}</span>
            </div>
        @endforeach
    </div>

    <!-- Mobile Vertical progress timeline -->
    <div class="flex flex-col md:hidden gap-6 relative pl-6 border-l-2 border-slate-200">
        <!-- Active Connecting Line -->
        <div class="absolute left-[-2px] top-4 bottom-4 w-0.5 bg-gold z-0 transition-all duration-500" style="height: {{ ($currentIndex / (count($steps) - 1)) * 100 }}%"></div>

        @foreach($steps as $index => $step)
            @php
                $isActive = $index <= $currentIndex;
                $isCurrent = $index === $currentIndex;
                $isRejected = $step['key'] === 'rejected';
            @endphp
            <div class="flex items-center gap-4 relative z-10">
                <!-- Dot Indicator -->
                <div class="absolute left-[-31px] w-6 h-6 rounded-full flex items-center justify-center border-2 transition-all duration-300 {{ $isCurrent ? ($isRejected ? 'bg-rose-50 border-rose-500 text-rose-500 scale-105' : 'bg-gold border-gold text-[#001229] scale-105') : ($isActive ? 'bg-gold/10 border-gold text-gold' : 'bg-white border-slate-200 text-slate-400') }}">
                    <span class="material-symbols-outlined text-sm">{{ $step['icon'] }}</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold {{ $isCurrent ? ($isRejected ? 'text-rose-600' : 'text-[#001229]') : ($isActive ? 'text-slate-700' : 'text-slate-400') }}">{{ $step['label'] }}</h4>
                </div>
            </div>
        @endforeach
    </div>
</div>
