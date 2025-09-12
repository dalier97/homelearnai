{{-- Progress Bar Component --}}
@props([
    'value' => 0,
    'max' => 100,
    'label' => null,
    'color' => 'blue',
    'size' => 'medium',
    'animated' => false,
    'striped' => false,
    'showPercentage' => true
])

@php
    $percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
    
    $sizeClasses = [
        'small' => 'h-1',
        'medium' => 'h-2',
        'large' => 'h-3',
        'xl' => 'h-4'
    ];
    
    $colorClasses = [
        'blue' => 'bg-blue-600',
        'green' => 'bg-green-600',
        'red' => 'bg-red-600',
        'yellow' => 'bg-yellow-600',
        'indigo' => 'bg-indigo-600',
        'purple' => 'bg-purple-600',
        'pink' => 'bg-pink-600',
        'gray' => 'bg-gray-600'
    ];
    
    $heightClass = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($label || $showPercentage)
        <div class="flex justify-between items-center mb-1">
            @if($label)
                <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
            @endif
            @if($showPercentage)
                <span class="text-sm text-gray-500">{{ number_format($percentage, 1) }}%</span>
            @endif
        </div>
    @endif
    
    <div class="w-full bg-gray-200 rounded-full {{ $heightClass }}">
        <div 
            class="{{ $colorClass }} {{ $heightClass }} rounded-full transition-all duration-300 ease-out {{ $striped ? 'bg-stripes' : '' }} {{ $animated ? 'animate-pulse' : '' }}"
            style="width: {{ $percentage }}%"
            role="progressbar"
            aria-valuenow="{{ $value }}"
            aria-valuemin="0"
            aria-valuemax="{{ $max }}"
        ></div>
    </div>
    
    @if($value && $max)
        <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>{{ number_format($value) }}</span>
            <span>{{ number_format($max) }}</span>
        </div>
    @endif
</div>

{{-- Add striped pattern CSS if needed --}}
@if($striped)
    <style>
        .bg-stripes {
            background-image: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
        }
    </style>
@endif