@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Completion Header -->
    <div class="max-w-4xl mx-auto text-center mb-8">
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 rounded-2xl p-12 mb-8 border-4 border-purple-300">
            <!-- Celebration Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-24 h-24 bg-purple-500 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            
            <!-- Main Title -->
            <h1 class="text-4xl font-bold text-purple-800 mb-4">
                {{ __('Preview Complete!') }}
            </h1>
            
            <div class="text-xl text-purple-700 mb-2">
                {{ __('You\'ve explored all flashcards from') }}
            </div>
            <div class="text-2xl font-bold text-purple-800 mb-4">
                "{{ $unit->name }}"
            </div>
            
            <!-- Important Note -->
            <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4 mb-6 inline-block">
                <div class="flex items-center space-x-2 text-purple-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ __('This was preview mode - no learning progress was recorded') }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview Statistics -->
    <div class="max-w-4xl mx-auto mb-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">{{ __('Preview Summary') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Cards -->
                <div class="text-center p-6 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="text-3xl font-bold text-purple-600 mb-2">{{ $previewStats['total_cards'] }}</div>
                    <div class="text-sm text-purple-700 font-medium">{{ __('Total Cards') }}</div>
                </div>
                
                <!-- Cards Reviewed -->
                <div class="text-center p-6 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="text-3xl font-bold text-blue-600 mb-2">{{ $previewStats['answered_cards'] }}</div>
                    <div class="text-sm text-blue-700 font-medium">{{ __('Cards Reviewed') }}</div>
                </div>
                
                <!-- Accuracy -->
                <div class="text-center p-6 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-3xl font-bold text-green-600 mb-2">{{ $previewStats['accuracy_percentage'] }}%</div>
                    <div class="text-sm text-green-700 font-medium">{{ __('Practice Accuracy') }}</div>
                </div>
                
                <!-- Average Time -->
                <div class="text-center p-6 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="text-3xl font-bold text-orange-600 mb-2">{{ $previewStats['average_time_per_card'] }}s</div>
                    <div class="text-sm text-orange-700 font-medium">{{ __('Avg Time per Card') }}</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">{{ __('Preview Progress') }}</span>
                    <span class="text-sm text-gray-500">{{ $previewStats['answered_cards'] }} / {{ $previewStats['total_cards'] }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-purple-600 h-4 rounded-full" 
                         style="width: {{ $previewStats['answered_cards'] > 0 ? round(($previewStats['answered_cards'] / $previewStats['total_cards']) * 100) : 0 }}%"></div>
                </div>
            </div>
            
            <!-- Total Time -->
            @if($previewStats['total_time_seconds'] > 0)
                <div class="text-center text-gray-600">
                    <span class="font-medium">{{ __('Total Preview Time:') }}</span>
                    @if($previewStats['total_time_seconds'] >= 60)
                        {{ floor($previewStats['total_time_seconds'] / 60) }}{{ __('m') }} {{ $previewStats['total_time_seconds'] % 60 }}{{ __('s') }}
                    @else
                        {{ $previewStats['total_time_seconds'] }}{{ __('s') }}
                    @endif
                </div>
            @endif
        </div>
    </div>
    
    <!-- Next Steps -->
    <div class="max-w-4xl mx-auto mb-8">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-8 border border-blue-200">
            <h3 class="text-xl font-bold text-blue-800 mb-4 text-center">{{ __('Ready to Start Learning?') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Learning Benefits -->
                <div class="bg-white rounded-lg p-6 border border-blue-200">
                    <h4 class="font-semibold text-blue-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        {{ __('When Your Child Studies These Cards:') }}
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-2">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('Progress will be tracked and saved') }}
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('Spaced repetition will optimize learning') }}
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('Cards will be scheduled for review') }}
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('Long-term retention will improve') }}
                        </li>
                    </ul>
                </div>
                
                <!-- Quality Assessment -->
                <div class="bg-white rounded-lg p-6 border border-green-200">
                    <h4 class="font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        {{ __('Content Quality Check:') }}
                    </h4>
                    
                    @if($previewStats['total_cards'] >= 10)
                        <div class="flex items-center text-green-700 mb-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm">{{ __('Good variety of cards (10+ cards)') }}</span>
                        </div>
                    @elseif($previewStats['total_cards'] >= 5)
                        <div class="flex items-center text-yellow-600 mb-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L2.196 13.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <span class="text-sm">{{ __('Consider adding more cards for better retention') }}</span>
                        </div>
                    @else
                        <div class="flex items-center text-orange-600 mb-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L2.196 13.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <span class="text-sm">{{ __('Very few cards - consider adding more content') }}</span>
                        </div>
                    @endif
                    
                    @if($previewStats['accuracy_percentage'] >= 80)
                        <div class="flex items-center text-green-700 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('Good difficulty level for learning') }}
                        </div>
                    @elseif($previewStats['accuracy_percentage'] >= 60)
                        <div class="flex items-center text-yellow-600 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L2.196 13.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            {{ __('Moderate difficulty - good for growth') }}
                        </div>
                    @else
                        <div class="flex items-center text-orange-600 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L2.196 13.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            {{ __('Challenging content - may need review') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="max-w-4xl mx-auto text-center">
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <!-- Back to Unit -->
            <a href="{{ route('units.show', $unit->id) }}" 
               class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>{{ __('Back to Unit') }}</span>
            </a>
            
            <!-- Start Another Preview -->
            @if($previewStats['answered_cards'] < $previewStats['total_cards'])
                <a href="{{ route('units.flashcards.preview.start', $unit->id) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>{{ __('Preview Again') }}</span>
                </a>
            @endif
            
            <!-- View Dashboard -->
            <a href="{{ route('dashboard.parent') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2V7m0 0V5a2 2 0 012-2h6l2 2h6a2 2 0 012 2v2M7 13h10M7 17h10"/>
                </svg>
                <span>{{ __('View Dashboard') }}</span>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Clean up any remaining preview session data
document.addEventListener('DOMContentLoaded', function() {
    // Remove any leftover preview session data
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('flashcard_preview_')) {
            localStorage.removeItem(key);
        }
    });
});
</script>
@endpush

@push('styles')
<style>
/* Celebration animations */
@keyframes celebration {
    0% { transform: scale(0.8) rotate(-5deg); }
    50% { transform: scale(1.1) rotate(5deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.celebration-icon {
    animation: celebration 0.8s ease-in-out;
}

/* Stats card hover effects */
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
</style>
@endpush