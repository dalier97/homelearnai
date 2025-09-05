<div x-data="{ open: true }" 
     x-show="open"
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <!-- Backdrop -->
        <div @click="open = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
            <form hx-post="{{ isset($timeBlock->id) ? route('calendar.update', $timeBlock->id) : route('calendar.store') }}"
                  hx-target="#day-{{ $timeBlock->day_of_week ?? 1 }}"
                  hx-swap="outerHTML"
                  @submit="open = false">
                @if(isset($timeBlock->id))
                    @method('PUT')
                @endif
                @csrf
                
                <div class="bg-white px-6 pt-5 pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ isset($timeBlock->id) ? __('edit_time_block') : __('add_new_time_block') }}
                        </h3>
                        <button @click="open = false" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Child Selection (if multiple children) -->
                        @if($children->count() > 1)
                        <div>
                            <label for="child_id" class="block text-sm font-medium text-gray-700">{{ __('child') }}</label>
                            <select 
                                name="child_id" 
                                id="child_id"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('select_child') }}</option>
                                @foreach($children as $child)
                                    <option value="{{ $child->id }}" {{ ($selectedChildId == $child->id || (old('child_id', $timeBlock->child_id ?? '') == $child->id)) ? 'selected' : '' }}>
                                        {{ $child->name }} ({{ $child->age }}y)
                                    </option>
                                @endforeach
                            </select>
                            @error('child_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" name="child_id" value="{{ $children->first()?->id }}">
                        @endif

                        <!-- Day of Week -->
                        <div>
                            <label for="day_of_week" class="block text-sm font-medium text-gray-700">{{ __('day_of_week') }}</label>
                            <select 
                                name="day_of_week" 
                                id="day_of_week"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @foreach(\App\Models\TimeBlock::getDayOptions() as $dayNumber => $dayName)
                                    <option value="{{ $dayNumber }}" {{ (old('day_of_week', $timeBlock->day_of_week ?? '') == $dayNumber) ? 'selected' : '' }}>
                                        {{ $dayName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('day_of_week')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Time Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start Time -->
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700">{{ __('start_time') }}</label>
                                <input 
                                    type="time" 
                                    name="start_time" 
                                    id="start_time"
                                    value="{{ old('start_time', isset($timeBlock->start_time) ? substr($timeBlock->start_time, 0, 5) : '') }}"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('start_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- End Time -->
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700">{{ __('end_time') }}</label>
                                <input 
                                    type="time" 
                                    name="end_time" 
                                    id="end_time"
                                    value="{{ old('end_time', isset($timeBlock->end_time) ? substr($timeBlock->end_time, 0, 5) : '') }}"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('end_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Label/Activity -->
                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700">{{ __('activity') }}</label>
                            <input 
                                type="text" 
                                name="label" 
                                id="label"
                                value="{{ old('label', $timeBlock->label ?? '') }}"
                                required
                                placeholder="{{ __('activity_placeholder') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('label')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Common Time Block Suggestions -->
                        @if(!isset($timeBlock->id))
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-sm font-medium text-blue-900 mb-2">{{ __('quick_suggestions') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="fillSuggestion('Mathematics', '09:00', '10:00')" 
                                        class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                    {{ __('math_910am') }}
                                </button>
                                <button type="button" onclick="fillSuggestion('Reading', '10:15', '11:00')" 
                                        class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200">
                                    {{ __('reading_101511am') }}
                                </button>
                                <button type="button" onclick="fillSuggestion('Science', '11:15', '12:00')" 
                                        class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded hover:bg-purple-200">
                                    {{ __('science_111512pm') }}
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                    <button 
                        @click="open = false"
                        type="button" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        {{ __('cancel') }}
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 flex items-center space-x-2">
                        @if(isset($timeBlock->id))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>{{ __('update_block') }}</span>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span>{{ __('add_block') }}</span>
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function fillSuggestion(label, startTime, endTime) {
        document.getElementById('label').value = label;
        document.getElementById('start_time').value = startTime;
        document.getElementById('end_time').value = endTime;
    }
</script>