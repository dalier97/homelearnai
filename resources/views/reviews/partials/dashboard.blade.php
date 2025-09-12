{{-- Review Dashboard for selected child --}}
<div class="review-dashboard space-y-6">
    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">{{ __('due_today') }}</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $reviewStats['due_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">{{ __('new_cards') }}</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $reviewStats['new_cards'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">{{ __('mastered') }}</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $reviewStats['mastered'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">{{ __('retention') }}</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $reviewStats['retention_rate'] ?? 0 }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Review Analytics Section --}}
    <div class="review-analytics bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('performance_analytics') }}</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="total-reviews-count text-3xl font-bold text-gray-900">
                        {{ $reviewStats['total_reviews'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">{{ __('total_reviews') }}</div>
                </div>
                <div class="text-center">
                    <div class="retention-percentage text-3xl font-bold text-green-600">
                        {{ $reviewStats['retention_rate'] ?? 0 }}%
                    </div>
                    <div class="text-sm text-gray-500 mt-1">{{ __('retention_rate') }}</div>
                </div>
                <div class="text-center">
                    <div class="average-interval text-3xl font-bold text-blue-600">
                        @php
                            $avgInterval = collect($reviewQueue ?? [])->avg('interval_days') ?? 0;
                        @endphp
                        {{ round($avgInterval, 1) }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">{{ __('avg_interval_days') }}</div>
                </div>
            </div>

            {{-- Subject Performance Breakdown --}}
            <div class="subject-performance mt-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">{{ __('performance_by_status') }}</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-3 bg-yellow-50 rounded">
                        <div class="text-lg font-semibold text-yellow-800">{{ $reviewStats['learning'] ?? 0 }}</div>
                        <div class="text-xs text-yellow-600">{{ __('learning') }}</div>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded">
                        <div class="text-lg font-semibold text-blue-800">{{ $reviewStats['reviewing'] ?? 0 }}</div>
                        <div class="text-xs text-blue-600">{{ __('reviewing') }}</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded">
                        <div class="text-lg font-semibold text-green-800">{{ $reviewStats['mastered'] ?? 0 }}</div>
                        <div class="text-xs text-green-600">{{ __('mastered') }}</div>
                    </div>
                </div>
            </div>

            {{-- Difficulty Breakdown --}}
            <div class="difficulty-breakdown mt-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">{{ __('recent_performance') }}</h3>
                <div class="grid grid-cols-4 gap-2 text-center text-sm">
                    <div class="p-2 bg-red-50 rounded">
                        <div class="font-medium text-red-800">{{ __('again') }}</div>
                        <div class="text-red-600">{{ $reviewStats['performance_again'] ?? 0 }}</div>
                    </div>
                    <div class="p-2 bg-orange-50 rounded">
                        <div class="font-medium text-orange-800">{{ __('hard') }}</div>
                        <div class="text-orange-600">{{ $reviewStats['performance_hard'] ?? 0 }}</div>
                    </div>
                    <div class="p-2 bg-green-50 rounded">
                        <div class="font-medium text-green-800">{{ __('good') }}</div>
                        <div class="text-green-600">{{ $reviewStats['performance_good'] ?? 0 }}</div>
                    </div>
                    <div class="p-2 bg-blue-50 rounded">
                        <div class="font-medium text-blue-800">{{ __('easy') }}</div>
                        <div class="text-blue-600">{{ $reviewStats['performance_easy'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            {{-- Time Period Toggles (weekly/monthly) --}}
            <div class="mt-6 flex justify-center">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    <button type="button" onclick="showWeeklyStats()" id="weekly-toggle" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-l-lg hover:bg-blue-700">
                        {{ __('weekly') }}
                    </button>
                    <button type="button" onclick="showMonthlyStats()" id="monthly-toggle" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-100">
                        {{ __('monthly') }}
                    </button>
                </div>
            </div>

            {{-- Stats Views for Weekly/Monthly --}}
            <div class="weekly-stats mt-4" style="display: block;">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('this_week') }}</h4>
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold">{{ $reviewStats['weekly_reviews'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('reviews_lowercase') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-green-700">{{ $reviewStats['weekly_success'] ?? 0 }}%</div>
                            <div class="text-xs text-gray-600">{{ __('success') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-blue-700">{{ $reviewStats['weekly_avg_days'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('avg_days') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-yellow-700">{{ $reviewStats['weekly_new'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('new_lowercase') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="monthly-stats mt-4" style="display: none;">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('this_month') }}</h4>
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold">{{ $reviewStats['monthly_reviews'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('reviews_lowercase') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-green-700">{{ $reviewStats['monthly_success'] ?? 0 }}%</div>
                            <div class="text-xs text-gray-600">{{ __('success') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-blue-700">{{ $reviewStats['monthly_avg_days'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('avg_days') }}</div>
                        </div>
                        <div class="p-2 bg-white rounded">
                            <div class="text-lg font-semibold text-yellow-700">{{ $reviewStats['monthly_new'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('new_lowercase') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Review Slots --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('todays_review_slots') }}</h2>
        </div>
        <div class="p-6">
            @if($todaySlots->isEmpty())
                <div class="text-center py-8">
                    <div class="mx-auto h-8 w-8 text-gray-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">{{ __('no_review_slots_scheduled_for_today') }}</p>
                    <a href="{{ route('reviews.slots', $selectedChild->id) }}" class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-500">
                        {{ __('set_up_review_slots') }}
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($todaySlots as $slot)
                        @php
                            $isActive = $slot->isCurrentlyActive();
                            $isUpcoming = $slot->isUpcomingToday();
                            $minutesUntil = $slot->getMinutesUntilStart();
                        @endphp
                        <div class="review-slot border rounded-lg p-4 {{ $isActive ? 'border-green-500 bg-green-50' : ($isUpcoming ? 'border-blue-500 bg-blue-50' : 'border-gray-200') }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">{{ $slot->getTimeRange() }}</span>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $slot->getSlotTypeColor() }}">
                                    {{ $slot->getSlotTypeLabel() }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-600">
                                {{ __('duration') }}: {{ $slot->getFormattedDuration() }}
                            </div>
                            
                            @if($isActive)
                                <div class="mt-2 text-xs font-medium text-green-600">
                                    {{ __('active_now') }}!
                                </div>
                            @elseif($isUpcoming && $minutesUntil !== null)
                                <div class="mt-2 text-xs text-blue-600">
                                    {{ __('starts_in_minutes', ['minutes' => $minutesUntil]) }}
                                </div>
                            @else
                                <div class="mt-2 text-xs text-gray-500">
                                    {{ __('completed') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Review Queue and Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Review Queue --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('review_queue') }}</h2>
                    @if($reviewQueue->count() > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ __('items_due_for_review', ['count' => $reviewQueue->count()]) }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @if($reviewQueue->isEmpty())
                    <div class="text-center py-8">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('all_caught_up') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('no_reviews_due_right_now_great_work') }}</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($reviewQueue->take(5) as $review)
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        @if($review->isFlashcardReview())
                                            {{ $review->flashcard?->question ?? __('Unknown Flashcard') }}
                                        @else
                                            {{ $review->topic?->title ?? __('Unknown Topic') }}
                                        @endif
                                    </p>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $review->getStatusColor() }}">
                                            {{ ucfirst($review->status) }}
                                        </span>
                                        @if($review->isOverdue())
                                            <span class="text-xs text-red-600">{{ __('days_overdue', ['days' => abs($review->getDaysUntilDue())]) }}</span>
                                        @elseif($review->getDaysUntilDue() == 0)
                                            <span class="text-xs text-orange-600">{{ __('due_today') }}</span>
                                        @endif
                                        <span class="text-xs text-gray-500">{{ $review->repetitions }} reps</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($review->interval_days > 1)
                                        <span class="text-xs text-gray-500">{{ $review->getFormattedInterval() }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if($reviewQueue->count() > 5)
                            <div class="text-center py-2">
                                <span class="text-sm text-gray-500">... and {{ $reviewQueue->count() - 5 }} more</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 text-center">
                        <button onclick="openReviewSession({{ $selectedChild->id }})"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('start_review_session') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions and Stats --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('quick_actions') }}</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('reviews.slots', $selectedChild->id) }}"
                       class="flex items-center p-3 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('manage_review_slots') }}
                    </a>
                    
                    <a href="{{ route('planning.index', ['child_id' => $selectedChild->id]) }}"
                       class="flex items-center p-3 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        {{ __('planning_board') }}
                    </a>
                    
                    <a href="#" class="flex items-center p-3 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        {{ __('review_history') }}
                    </a>
                </div>
            </div>

            {{-- Review Stats Card --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('review_stats') }}</h3>
                </div>
                <div class="review-stats p-6">
                    <div class="space-y-4">
                        <div class="retention-rate">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{{ __('overall_retention') }}</span>
                                <span>{{ $reviewStats['retention_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $reviewStats['retention_rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3 text-center text-sm">
                            <div class="p-3 bg-blue-50 rounded">
                                <div class="text-lg font-semibold text-blue-800">{{ $reviewStats['due_today'] ?? 0 }}</div>
                                <div class="text-xs text-blue-600">{{ __('due_today') }}</div>
                            </div>
                            @if(($reviewStats['overdue'] ?? 0) > 0)
                            <div class="p-3 bg-red-50 rounded">
                                <div class="text-lg font-semibold text-red-800">{{ $reviewStats['overdue'] }}</div>
                                <div class="text-xs text-red-600">{{ __('overdue') }}</div>
                            </div>
                            @endif
                        </div>

                        {{-- Next Review Session --}}
                        <div class="next-review-session mt-4 p-3 bg-gray-50 rounded">
                            <div class="text-sm font-medium text-gray-900 mb-1">{{ __('next_scheduled_review') }}</div>
                            @if(isset($allSlots) && $allSlots->isNotEmpty())
                                @php 
                                    // Sort all slots by day of week and time to find the next one
                                    // Laravel Carbon: Monday = 1, Sunday = 7 (ISO format)
                                    $currentDayOfWeek = now()->dayOfWeekIso; // 1=Mon, 2=Tue, ..., 7=Sun  
                                    $currentTime = now()->format('H:i:s');
                                    
                                    $nextSlot = $allSlots->map(function($slot) use ($currentDayOfWeek, $currentTime) {
                                        // Calculate days until this slot
                                        if ($slot->day_of_week == $currentDayOfWeek && $slot->start_time > $currentTime) {
                                            // Today and still upcoming
                                            $daysUntil = 0;
                                        } else {
                                            // Future day or today but passed
                                            $daysUntil = ($slot->day_of_week - $currentDayOfWeek + 7) % 7;
                                            if ($daysUntil == 0) $daysUntil = 7; // Next week
                                        }
                                        
                                        $slot->_sort_key = $daysUntil * 1440 + (int)\Carbon\Carbon::parse($slot->start_time)->format('Hi'); // Minutes + time
                                        return $slot;
                                    })->sortBy('_sort_key')->first();
                                @endphp
                                @if($nextSlot)
                                    <div class="next-scheduled-review text-sm text-gray-600">
                                        @php
                                            $currentDayOfWeekISO = now()->dayOfWeekIso;
                                            $dayNames = ['', __('monday'), __('tuesday'), __('wednesday'), __('thursday'), __('friday'), __('saturday'), __('sunday')];
                                        @endphp
                                        <span>{{ $nextSlot->day_of_week == $currentDayOfWeekISO ? __('today') : $dayNames[$nextSlot->day_of_week] }}</span>
                                        at <span>{{ \Carbon\Carbon::parse($nextSlot->start_time)->translatedFormat('g:i A') }}</span>
                                    </div>
                                @else
                                    <div class="text-sm text-gray-500">{{ __('no_scheduled_review_slots') }}</div>
                                @endif
                            @elseif($todaySlots->isNotEmpty())
                                @php $nextSlot = $todaySlots->first() @endphp
                                <div class="next-scheduled-review text-sm text-gray-600">
                                    <span>{{ $nextSlot->day_of_week == now()->dayOfWeek ? __('today') : now()->addDays($nextSlot->day_of_week - now()->dayOfWeek)->translatedFormat('l') }}</span>
                                    at <span>{{ \Carbon\Carbon::parse($nextSlot->start_time)->translatedFormat('g:i A') }}</span>
                                </div>
                            @else
                                <div class="text-sm text-gray-500">{{ __('no_scheduled_review_slots') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Learning Progress --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('learning_progress') }}</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{{ __('learning') }}</span>
                                <span>{{ $reviewStats['learning'] ?? 0 }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $reviewStats['total_reviews'] > 0 ? (($reviewStats['learning'] ?? 0) / $reviewStats['total_reviews']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{{ __('reviewing') }}</span>
                                <span>{{ $reviewStats['reviewing'] ?? 0 }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $reviewStats['total_reviews'] > 0 ? (($reviewStats['reviewing'] ?? 0) / $reviewStats['total_reviews']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{{ __('mastered') }}</span>
                                <span>{{ $reviewStats['mastered'] ?? 0 }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $reviewStats['total_reviews'] > 0 ? (($reviewStats['mastered'] ?? 0) / $reviewStats['total_reviews']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Review History Table --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('review_history') }}</h3>
                <button class="text-sm text-blue-600 hover:text-blue-500">{{ __('view_all') }}</button>
            </div>
        </div>
        <div class="p-6">
            <div class="review-history-table">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('date') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('topic') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('performance') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('next_review') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reviewQueue->take(5) as $review)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-600">
                                    {{ $review->last_reviewed_at?->translatedFormat('M j') ?? __('never') }}
                                </td>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900">
                                    @if($review->isFlashcardReview())
                                        {{ $review->flashcard?->question ?? __('Unknown Flashcard') }}
                                    @else
                                        {{ $review->topic?->title ?? __('Unknown Topic') }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $review->getStatusColor() }}">
                                        {{ ucfirst($review->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600">
                                    {{ $review->due_date?->translatedFormat('M j') ?? __('not_available') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">
                                    {{ __('no_review_history_yet') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>