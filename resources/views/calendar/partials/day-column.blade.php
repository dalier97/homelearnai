<div id="day-{{ $day }}" class="border-r border-gray-200 last:border-r-0 p-2 bg-gray-50 min-h-96 relative">
    <!-- Add Time Block Button -->
    @if($selectedChild)
    <div class="mb-3">
        <button
            hx-get="{{ route('calendar.create') }}?child_id={{ $selectedChild->id }}&day_of_week={{ $day }}"
            hx-target="#time-block-form-modal"
            hx-swap="innerHTML"
            class="w-full py-2 px-3 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-300 transition"
        >
            <svg class="w-4 h-4 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Block
        </button>
    </div>
    @endif

    <!-- Time Blocks -->
    <div class="space-y-2">
        @forelse($timeBlocks as $timeBlock)
            <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-3 hover:shadow-md transition-shadow group">
                <!-- Time Block Content -->
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900 text-sm">{{ $timeBlock->label }}</h4>
                        <p class="text-xs text-gray-600 mt-1">{{ $timeBlock->getTimeRange() }}</p>
                        <p class="text-xs text-blue-600">{{ $timeBlock->getFormattedDuration() }}</p>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            hx-get="{{ route('calendar.edit', $timeBlock->id) }}"
                            hx-target="#time-block-form-modal"
                            hx-swap="innerHTML"
                            class="p-1 text-gray-400 hover:text-blue-600 rounded"
                            title="Edit"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button
                            hx-delete="{{ route('calendar.destroy', $timeBlock->id) }}"
                            hx-target="#day-{{ $day }}"
                            hx-swap="outerHTML"
                            hx-confirm="Are you sure you want to delete this time block?"
                            class="p-1 text-gray-400 hover:text-red-600 rounded"
                            title="Delete"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            @if(isset($reviewSlots) && $reviewSlots->isEmpty() && $selectedChild)
            <div class="text-center text-gray-400 text-sm py-8">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <p>No blocks</p>
            </div>
            @endif
        @endforelse

        <!-- Review Slots -->
        @if(isset($reviewSlots) && $reviewSlots->isNotEmpty())
            @foreach($reviewSlots as $reviewSlot)
                <div class="bg-green-50 rounded-lg shadow-sm border-l-4 border-green-500 p-2 hover:shadow-md transition-shadow group">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <h4 class="font-medium text-green-800 text-xs">Review Time</h4>
                            </div>
                            <p class="text-xs text-green-700 mt-1">{{ $reviewSlot->getTimeRange() }}</p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs rounded-full {{ $reviewSlot->getSlotTypeColor() }}">
                                    {{ $reviewSlot->slot_type === 'micro' ? 'Micro' : 'Standard' }}
                                </span>
                                <span class="text-xs text-green-600">{{ $reviewSlot->getFormattedDuration() }}</span>
                            </div>
                        </div>
                        
                        @if($reviewSlot->isCurrentlyActive())
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </div>
                        @elseif($reviewSlot->isUpcomingToday() && $reviewSlot->getMinutesUntilStart() <= 60)
                            <div class="flex items-center">
                                <span class="text-xs text-green-600">{{ $reviewSlot->getMinutesUntilStart() }}m</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>