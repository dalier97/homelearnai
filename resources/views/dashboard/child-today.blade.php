@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header - Child-friendly -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">{{ __(':name\'s Learning Today', ['name' => $child->name]) }} 🌟</h2>
                <p class="text-blue-100 mt-1">{{ date('l, F j, Y') }} - {{ __('Let\'s make today amazing!') }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ $today_sessions->count() }}</div>
                <div class="text-sm text-blue-100">{{ trans_choice('session today|sessions today', $today_sessions->count()) }}</div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">{{ __('Today\'s Sessions') }}</p>
                    <p class="text-2xl font-bold text-green-900">{{ $today_sessions->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">{{ __('Review Queue') }}</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $review_queue->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">{{ __('Independence') }}</p>
                    <p class="text-lg font-bold text-purple-900">{{ __('Level :level', ['level' => $child->independence_level]) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Learning Sessions -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('Today\'s Sessions') }}
                </h3>
                @if($can_reorder)
                    <span class="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ __('You can reorder these!') }}</span>
                @endif
            </div>
        </div>
        <div id="today-sessions" class="p-6">
            @if($today_sessions->count() > 0)
                <div class="space-y-4" @if($can_reorder) x-data="{ reorderEnabled: true }" @endif>
                    @foreach($today_sessions as $index => $session)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow session-card"
                             @if($can_reorder) draggable="true" data-session-id="{{ $session->id }}" @endif>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        @if($can_reorder)
                                            <svg class="w-5 h-5 text-gray-400 cursor-move" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                        @endif
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">
                                                {{ $session->topic->title ?? __('Learning Session #:id', ['id' => $session->id]) }}
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                {{ __('Estimated time: :minutes minutes', ['minutes' => $session->estimated_minutes]) }}
                                                @if($session->scheduled_start_time)
                                                    • {{ __('Scheduled for :time', ['time' => Carbon\Carbon::parse($session->scheduled_start_time)->translatedFormat('g:i A')]) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    @if($session->status === 'completed')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('Complete!') }}
                                        </span>
                                    @else
                                        <button hx-post="{{ route('dashboard.sessions.complete', $session->id) }}" 
                                                hx-target="closest .session-card"
                                                hx-swap="outerHTML"
                                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ __('Complete') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Evidence Capture (shown after completion) -->
                            @if($session->status === 'completed' && !$session->hasEvidence())
                                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                                    <h5 class="text-sm font-medium text-blue-900 mb-2">📸 {{ __('Show what you learned!') }}</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Photo') }}</span>
                                        </button>
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Voice Note') }}</span>
                                        </button>
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Write Notes') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No sessions scheduled') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Enjoy your free time!') }} 🎉</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Review Queue -->
    @if($review_queue->count() > 0)
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    {{ trans_choice('Quick Review (:count item)|Quick Review (:count items)', $review_queue->count(), ['count' => $review_queue->count()]) }}
                </h3>
            </div>
            <div class="p-6">
                <div class="text-center">
                    <p class="text-gray-600 mb-4">{{ __('Let\'s review some things you\'ve learned before!') }}</p>
                    <a href="{{ route('reviews.session', $child->id) }}" 
                       class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        {{ __('Start Review Session') }}
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- This Week View (for Level 3+ independence) -->
    @if($can_move_weekly && !empty($week_sessions))
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ __('This Week\'s Plan') }}
                    <span class="ml-2 text-sm bg-purple-100 text-purple-700 px-2 py-1 rounded">{{ __('You can move sessions!') }}</span>
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-7 gap-4">
                    @foreach($week_sessions as $dayOfWeek => $dayData)
                        <div class="text-center">
                            <h4 class="font-medium text-gray-900 mb-2">{{ substr($dayData['day_name'], 0, 3) }}</h4>
                            <div class="text-sm text-gray-500 mb-3">{{ $dayData['date']->translatedFormat('M j') }}</div>
                            <div class="space-y-2">
                                @foreach($dayData['sessions']->take(3) as $session)
                                    <div class="bg-gray-50 p-2 rounded text-xs border session-movable" 
                                         draggable="true" data-session-id="{{ $session->id }}">
                                        {{ $session->topic->title ?? __('Session') }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Celebration Section -->
    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg shadow-lg p-6 text-white text-center">
        <h3 class="text-lg font-bold mb-2">🎉 {{ __('You\'re doing great!') }} 🎉</h3>
        <p class="text-yellow-100">{{ __('Keep up the awesome learning, :name!', ['name' => $child->name]) }}</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Handle session completion
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.xhr.status === 200 && event.detail.elt.textContent.includes('Complete')) {
            showToast('{{ __('Great job! Session completed!') }} 🌟', 'success');
        }
    });

    // Drag and drop for reordering (if allowed)
    @if($can_reorder)
    document.addEventListener('DOMContentLoaded', function() {
        const sessionCards = document.querySelectorAll('.session-card');
        
        sessionCards.forEach(card => {
            card.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.sessionId);
                this.style.opacity = '0.5';
            });

            card.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
            });

            card.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#f0f9ff';
            });

            card.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });

            card.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const draggedId = e.dataTransfer.getData('text/plain');
                const targetId = this.dataset.sessionId;
                
                if (draggedId !== targetId) {
                    // Here you would implement the reorder logic
                    showToast('{{ __('Sessions reordered!') }} 📝', 'success');
                }
            });
        });
    });
    @endif

    // Weekly session moving (if allowed)
    @if($can_move_weekly)
    document.addEventListener('DOMContentLoaded', function() {
        const movableSessions = document.querySelectorAll('.session-movable');
        
        movableSessions.forEach(session => {
            session.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.sessionId);
            });
        });

        // Add drop zones for each day
        document.querySelectorAll('.grid.grid-cols-7 > div').forEach(dayColumn => {
            dayColumn.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#f3e8ff';
            });

            dayColumn.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });

            dayColumn.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const sessionId = e.dataTransfer.getData('text/plain');
                // Here you would implement the move session logic
                showToast('{{ __('Session moved to different day!') }} 📅', 'success');
            });
        });
    });
    @endif
</script>
@endpush