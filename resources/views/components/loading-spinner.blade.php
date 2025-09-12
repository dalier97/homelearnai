{{-- Loading Spinner Component --}}
@props([
    'size' => 'medium',
    'color' => 'blue',
    'text' => null,
    'overlay' => false,
    'centered' => false
])

@php
    $sizeClasses = [
        'small' => 'w-4 h-4',
        'medium' => 'w-6 h-6',
        'large' => 'w-8 h-8',
        'xl' => 'w-12 h-12'
    ];
    
    $colorClasses = [
        'blue' => 'text-blue-600',
        'gray' => 'text-gray-600',
        'green' => 'text-green-600',
        'red' => 'text-red-600',
        'yellow' => 'text-yellow-600',
        'indigo' => 'text-indigo-600',
        'purple' => 'text-purple-600',
        'pink' => 'text-pink-600'
    ];
    
    $spinnerSize = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $spinnerColor = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

{{-- Overlay version --}}
@if($overlay)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="backdrop-filter: blur(2px);">
        <div class="bg-white rounded-lg p-6 shadow-xl max-w-sm mx-4">
            <div class="flex flex-col items-center space-y-4">
                <svg class="animate-spin {{ $spinnerSize }} {{ $spinnerColor }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                @if($text)
                    <div class="text-sm text-gray-700 text-center">{{ $text }}</div>
                @endif
            </div>
        </div>
    </div>
{{-- Centered version --}}
@elseif($centered)
    <div class="flex flex-col items-center justify-center py-8 space-y-4">
        <svg class="animate-spin {{ $spinnerSize }} {{ $spinnerColor }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <div class="text-sm text-gray-600 text-center">{{ $text }}</div>
        @endif
    </div>
{{-- Inline version --}}
@else
    <div class="flex items-center space-x-2 {{ $attributes->get('class') }}">
        <svg class="animate-spin {{ $spinnerSize }} {{ $spinnerColor }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <span class="text-sm text-gray-600">{{ $text }}</span>
        @endif
    </div>
@endif