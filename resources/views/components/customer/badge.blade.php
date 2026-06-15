@props(['status'])

@php
    $statusLower = strtolower(str_replace('_', ' ', $status));
    
    $config = match($statusLower) {
        'approved' => [
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200/50',
            'dot' => 'bg-emerald-500',
            'label' => 'Approved'
        ],
        'submitted' => [
            'class' => 'bg-blue-50 text-blue-700 border-blue-200/50',
            'dot' => 'bg-blue-500',
            'label' => 'Submitted'
        ],
        'under review', 'review' => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200/50',
            'dot' => 'bg-amber-500',
            'label' => 'Under Review'
        ],
        'rejected' => [
            'class' => 'bg-rose-50 text-rose-700 border-rose-200/50',
            'dot' => 'bg-rose-500',
            'label' => 'Rejected'
        ],
        'dispatched', 'shipped' => [
            'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200/50',
            'dot' => 'bg-cyan-500',
            'label' => 'Dispatched'
        ],
        default => [
            'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            'dot' => 'bg-slate-400',
            'label' => ucfirst($status)
        ]
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {$config['class']}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }}"></span>
    <span>{{ $config['label'] }}</span>
</span>
