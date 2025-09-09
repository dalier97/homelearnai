@if($topics->count() > 0)
    <div class="space-y-4">
        @foreach($topics as $topic)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $topic->title }}</h3>
                            @if($topic->required)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ __('required') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ __('optional') }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="text-sm text-gray-600 space-y-1">
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $topic->getEstimatedDuration() }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center space-x-2">
                        <button 
                            hx-get="{{ route('topics.edit', [$subject->id, $unit->id, $topic->id]) }}"
                            hx-target="#topic-modal"
                            hx-swap="innerHTML"
                            class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button 
                            hx-delete="{{ route('topics.destroy', [$subject->id, $unit->id, $topic->id]) }}"
                            hx-target="#topics-list"
                            hx-swap="innerHTML"
                            hx-confirm="{{ __('confirm_delete_topic') }}"
                            class="text-red-400 hover:text-red-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_topics_yet') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('get_started_creating_first_topic_in_unit') }}</p>
        <div class="mt-6">
            <button 
                type="button"
                hx-get="{{ route('topics.create', [$subject->id, $unit->id]) }}"
                hx-target="#topic-modal"
                hx-swap="innerHTML"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                style="background-color: {{ $subject->color }}">
                {{ __('add_topic') }}
            </button>
        </div>
    </div>
@endif