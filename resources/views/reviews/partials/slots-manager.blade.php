{{-- Review Slots Weekly Manager --}}
<div class="px-6 py-4 border-b border-gray-200">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('weekly_schedule') }}</h2>
        <button onclick="openAddSlotModal()" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            {{ __('add_slot') }}
        </button>
    </div>
</div>

@if(isset($error))
    <div class="px-6 py-3 bg-red-50 border-b border-red-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-800 error">{{ $error }}</p>
            </div>
        </div>
    </div>
@endif

<div class="p-6">
    <div class="space-y-6">
        @php
            $dayNames = ['', __('monday'), __('tuesday'), __('wednesday'), __('thursday'), __('friday'), __('saturday'), __('sunday')];
        @endphp
        
        @foreach(range(1, 7) as $day)
            <div class="border border-gray-200 rounded-lg">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">{{ $dayNames[$day] }}</h3>
                </div>
                
                <div id="day-{{ $day }}-slots" class="p-4">
                    @include('reviews.partials.day-slots', ['day' => $day, 'slots' => $weeklySlots[$day] ?? collect(), 'child' => $child])
                </div>
            </div>
        @endforeach
    </div>
</div>