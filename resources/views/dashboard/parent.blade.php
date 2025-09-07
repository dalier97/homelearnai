@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Parent Dashboard') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('Week of :date - Multi-child oversight and planning', ['date' => $week_start->translatedFormat('M j, Y')]) }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('planning.index') }}" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <span>{{ __('Planning Board') }}</span>
                </a>
                <a href="{{ route('children.index') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>{{ __('Manage Children') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Weekly Overview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($dashboard_data as $data)
            @php $child = $data['child']; @endphp
            <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                <!-- Child Header -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $child->name }}</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>{{ __('Age :age', ['age' => $child->age]) }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="px-2 py-1 bg-{{ $data['capacity_status']['status']['color'] }}-100 text-{{ $data['capacity_status']['status']['color'] }}-700 rounded text-xs">
                                {{ $data['capacity_status']['status']['label'] }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <!-- Child Today View -->
                        <a href="{{ route('dashboard.child-today', $child->id) }}" 
                           class="text-blue-600 hover:text-blue-800 p-2 rounded" title="{{ __('Child View') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <!-- Settings -->
                        <button hx-get="{{ route('children.edit', $child->id) }}" 
                                hx-target="#child-settings-modal" 
                                hx-swap="innerHTML"
                                class="text-gray-400 hover:text-gray-600 p-2 rounded" title="{{ __('Settings') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Capacity Meter -->
                <div class="mb-4">
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="text-gray-600">{{ __('Week Progress') }}</span>
                        <span class="font-medium">{{ trans_choice(':completed/:total session|:completed/:total sessions', $data['capacity_status']['total_sessions'], ['completed' => $data['capacity_status']['completed_sessions'], 'total' => $data['capacity_status']['total_sessions']]) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-{{ $data['capacity_status']['status']['color'] }}-500 h-3 rounded-full transition-all" 
                             style="width: {{ $data['capacity_status']['completion_percentage'] }}%"></div>
                    </div>
                </div>

                <!-- Today's Sessions -->
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">{{ trans_choice('Today (:count session)|Today (:count sessions)', $data['today_sessions']->count(), ['count' => $data['today_sessions']->count()]) }}</h4>
                    @if($data['today_sessions']->count() > 0)
                        <div class="space-y-2">
                            @foreach($data['today_sessions']->take(3) as $session)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                                    <span class="flex-1">{{ $session->topic->title ?? 'Session #' . $session->id }}</span>
                                    <span class="text-{{ $session->status === 'completed' ? 'green' : 'gray' }}-600">
                                        {{ trans_choice(':minutes min|:minutes mins', $session->estimated_minutes, ['minutes' => $session->estimated_minutes]) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">{{ __('No sessions scheduled for today') }}</p>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <!-- Quick Complete Today -->
                    <button hx-post="{{ route('dashboard.bulk-complete-today') }}" 
                            hx-vals='{"child_id": {{ $child->id }}}' 
                            hx-confirm="{{ __('Mark all today\'s sessions as complete?') }}"
                            class="flex items-center justify-center space-x-1 bg-green-100 text-green-700 px-3 py-2 rounded hover:bg-green-200 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ __('Complete Today') }}</span>
                    </button>

                    <!-- Reviews -->
                    <a href="{{ route('reviews.session', $child->id) }}" 
                       class="flex items-center justify-center space-x-1 bg-blue-100 text-blue-700 px-3 py-2 rounded hover:bg-blue-200 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span>{{ trans_choice(':count Review|:count Reviews', $data['review_queue_count'], ['count' => $data['review_queue_count']]) }}</span>
                    </a>
                </div>

                <!-- Kids Mode Button -->
                @if($pin_is_set)
                    <button hx-post="{{ route('kids-mode.enter', $child->id) }}" 
                            hx-confirm="{{ __('kids_mode_for', ['name' => $child->name]) }}?" 
                            class="w-full flex items-center justify-center space-x-2 bg-purple-100 text-purple-700 px-3 py-2 rounded hover:bg-purple-200 text-sm kids-mode-enter-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1a1 1 0 00-1-1H7a1 1 0 00-1 1v1a2 2 0 002 2zM9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ __('enter_kids_mode') }}</span>
                        <div class="htmx-indicator">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </button>
                @else
                    <a href="{{ route('kids-mode.settings') }}" 
                       class="w-full flex items-center justify-center space-x-2 bg-gray-100 text-gray-600 px-3 py-2 rounded hover:bg-gray-200 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1a1 1 0 00-1-1H7a1 1 0 00-1 1v1a2 2 0 002 2zM9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ __('set_pin_first') }}</span>
                    </a>
                @endif

                <!-- Independence Level -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('Independence:') }}</span>
                        <select hx-put="{{ route('dashboard.independence-level', $child->id) }}" 
                                hx-include="this"
                                name="independence_level"
                                class="text-sm border-0 bg-transparent text-gray-900 font-medium focus:ring-0">
                            @for($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ $child->independence_level === $i ? 'selected' : '' }}>
                                    {{ __('Level :level', ['level' => $i]) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $child->getIndependenceLevelLabel() }}</p>
                </div>

                <!-- Catch-up Notice -->
                @if($data['catch_up_count'] > 0)
                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L2.196 13.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <span class="text-sm text-yellow-800">{{ trans_choice(':count session needs catch-up|:count sessions need catch-up', $data['catch_up_count'], ['count' => $data['catch_up_count']]) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Weekly Progress Chart -->
    @if($children->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('This Week\'s Progress') }}</h3>
            <div class="space-y-4">
                @foreach($dashboard_data as $data)
                    @php $child = $data['child']; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">{{ $child->name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ trans_choice(':completed/:total session|:completed/:total sessions', collect($data['weekly_progress'])->sum('total'), ['completed' => collect($data['weekly_progress'])->sum('completed'), 'total' => collect($data['weekly_progress'])->sum('total')]) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            @foreach($data['weekly_progress'] as $day)
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">{{ $day['day'] }}</div>
                                    <div class="h-12 bg-gray-100 rounded flex items-center justify-center relative">
                                        @if($day['total'] > 0)
                                            <div class="absolute inset-0 bg-green-200 rounded" 
                                                 style="height: {{ $day['percentage'] }}%; top: {{ 100 - $day['percentage'] }}%;"></div>
                                            <span class="text-xs font-medium relative z-10">{{ $day['completed'] }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Modals -->
<div id="child-settings-modal"></div>

@endsection

@push('scripts')
<script>
// Kids Mode UI JavaScript - Inline version to avoid asset compilation issues
class KidsModeUI {
    constructor() {
        this.initEnterButtonHandlers();
        this.initBackButtonPrevention();
    }

    initEnterButtonHandlers() {
        document.addEventListener('click', (event) => {
            if (event.target.closest('.kids-mode-enter-btn')) {
                const button = event.target.closest('.kids-mode-enter-btn');
                this.handleEnterKidsMode(button);
            }
        });

        document.body.addEventListener('htmx:beforeRequest', (event) => {
            if (event.detail.elt.classList.contains('kids-mode-enter-btn')) {
                this.showEnterLoading(event.detail.elt);
            }
        });

        document.body.addEventListener('htmx:afterRequest', (event) => {
            if (event.detail.elt.classList.contains('kids-mode-enter-btn')) {
                this.handleEnterResponse(event);
            }
        });
    }

    handleEnterKidsMode(button) {
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');
        const indicator = button.querySelector('.htmx-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';
        }
    }

    showEnterLoading(button) {
        const text = button.querySelector('span:not(.htmx-indicator span)');
        if (text) {
            text.textContent = '{{ __("Entering Kids Mode...") }}';
        }
        button.style.transform = 'scale(0.98)';
        setTimeout(() => button.style.transform = 'scale(1)', 150);
    }

    handleEnterResponse(event) {
        const button = event.detail.elt;
        const indicator = button.querySelector('.htmx-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
        
        if (event.detail.xhr.status === 200) {
            const response = JSON.parse(event.detail.xhr.responseText);
            if (response.message) {
                showToast(response.message, 'success');
            }
        }
    }

    initBackButtonPrevention() {
        if (document.querySelector('[data-testid="kids-mode-indicator"]')) {
            history.pushState(null, null, location.href);
            window.addEventListener('popstate', () => {
                history.pushState(null, null, location.href);
                showToast('{{ __("Ask a parent to help exit Kids Mode") }}', 'info', 5000);
            });
        }
    }
}

// Initialize on DOM loaded
document.addEventListener('DOMContentLoaded', () => {
    window.kidsModeUI = new KidsModeUI();
});
</script>
<script>
    // Handle bulk complete success
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.xhr.status === 200) {
            const response = JSON.parse(event.detail.xhr.responseText);
            if (response.success) {
                showToast(response.message || '{{ __('Tasks completed successfully') }}', 'success');
                // Refresh the page to show updated status
                setTimeout(() => window.location.reload(), 1000);
            }
        }
    });

    // Handle independence level updates
    document.body.addEventListener('change', function(event) {
        if (event.target.name === 'independence_level') {
            showToast('{{ __('Independence level updated!') }}', 'success');
        }
    });

    // Add missing translations for kids mode
    if (window.translations) {
        window.translations.entering_kids_mode = '{{ __('entering_kids_mode', [], 'Entering Kids Mode...') }}';
        window.translations.ask_parent_for_help = '{{ __('Ask a parent to help exit Kids Mode') }}';
    }
</script>
@endpush