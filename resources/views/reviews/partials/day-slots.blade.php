{{-- Day Slots Display --}}
@if(isset($error))
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 error">
        {{ $error }}
    </div>
@endif

@if($slots->isEmpty())
    <div class="text-center py-6 text-gray-500">
        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="mt-2 text-sm">{{ __('no_review_slots_scheduled') }}</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($slots as $slot)
            <div class="review-slot flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center">
                        @if($slot->is_active)
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        @else
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-2"></div>
                        @endif
                        <span class="text-sm font-medium text-gray-900" data-time-range="{{ $slot->getTimeRange() }}">{{ $slot->getTimeRange() }}</span>
                    </div>
                    
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $slot->getSlotTypeColor() }}">
                        {{ $slot->getSlotTypeLabel() }}
                    </span>
                    
                    <span class="text-xs text-gray-500">{{ $slot->getFormattedDuration() }}</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    {{-- Toggle Active Status --}}
                    <button hx-put="{{ route('reviews.slots.toggle', $slot->id) }}" 
                            hx-target="#day-{{ $day }}-slots"
                            hx-swap="innerHTML"
                            class="px-2 py-1 text-xs border rounded {{ $slot->is_active ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700' }}" 
                            title="{{ $slot->is_active ? __('deactivate') : __('activate') }} slot">
                        {{ $slot->is_active ? __('disable') : __('enable') }}
                    </button>
                    
                    {{-- Edit Slot --}}
                    <button onclick="editSlot({{ $slot->id }}, {{ json_encode($slot->toArray()) }})" 
                            class="p-1 text-gray-400 hover:text-gray-600 focus:outline-none" 
                            title="{{ __('edit_slot') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    
                    {{-- Delete Slot --}}
                    <button hx-delete="{{ route('reviews.slots.destroy', $slot->id) }}" 
                            hx-target="#day-{{ $day }}-slots"
                            hx-swap="innerHTML"
                            hx-confirm="{{ __('are_you_sure_you_want_to_delete_this_review_slot') }}"
                            class="p-1 text-gray-400 hover:text-red-600 focus:outline-none" 
                            title="{{ __('delete_slot') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Edit Slot Modal --}}
<div id="edit-slot-modal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 relative">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-10">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('edit_review_slot') }}</h3>
        </div>
        
        <form id="edit-slot-form">
            <div class="px-6 py-4 space-y-4">
                <input type="hidden" id="edit-slot-id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('day_of_week') }}</label>
                    <select id="edit-day-of-week" name="day_of_week" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">{{ __('monday') }}</option>
                        <option value="2">{{ __('tuesday') }}</option>
                        <option value="3">{{ __('wednesday') }}</option>
                        <option value="4">{{ __('thursday') }}</option>
                        <option value="5">{{ __('friday') }}</option>
                        <option value="6">{{ __('saturday') }}</option>
                        <option value="7">{{ __('sunday') }}</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('start_time') }}</label>
                        <input type="time" id="edit-start-time" name="start_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('end_time') }}</label>
                        <input type="time" id="edit-end-time" name="end_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('slot_type') }}</label>
                    <select id="edit-slot-type" name="slot_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="micro">{{ __('micro_session_5_min') }}</option>
                        <option value="standard">{{ __('standard_session_1530_min') }}</option>
                    </select>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" onclick="closeEditSlotModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    {{ __('cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    {{ __('update_slot') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editSlot(slotId, slotData) {
    document.getElementById('edit-slot-id').value = slotId;
    document.getElementById('edit-day-of-week').value = slotData.day_of_week;
    
    // Convert time format from HH:MM:SS to HH:MM for input
    const startTime = slotData.start_time ? slotData.start_time.substring(0, 5) : '';
    const endTime = slotData.end_time ? slotData.end_time.substring(0, 5) : '';
    
    document.getElementById('edit-start-time').value = startTime;
    document.getElementById('edit-end-time').value = endTime;
    document.getElementById('edit-slot-type').value = slotData.slot_type;
    
    document.getElementById('edit-slot-modal').classList.remove('hidden');
}

function closeEditSlotModal() {
    document.getElementById('edit-slot-modal').classList.add('hidden');
    document.getElementById('edit-slot-form').reset();
}

// Handle edit form submission
document.getElementById('edit-slot-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const slotId = document.getElementById('edit-slot-id').value;
    const formData = new FormData(this);
    
    fetch(`/reviews/slots/${slotId}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            day_of_week: formData.get('day_of_week'),
            start_time: formData.get('start_time'),
            end_time: formData.get('end_time'),
            slot_type: formData.get('slot_type')
        })
    })
    .then(response => response.text())
    .then(html => {
        const dayOfWeek = formData.get('day_of_week');
        document.getElementById(`day-${dayOfWeek}-slots`).innerHTML = html;
        closeEditSlotModal();
    })
    .catch(error => console.error(window.__('Error updating slot:'), error));
});
</script>