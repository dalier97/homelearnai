<div id="child-{{ $child->id }}" class="child-item bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <!-- Child Avatar -->
            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-lg">
                {{ substr($child->name, 0, 1) }}
            </div>
            
            <!-- Child Info -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $child->name }}</h3>
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('age_years', ['age' => $child->age]) }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-6m-4 0H3m2 0h6M7 3v6h6V3"/>
                        </svg>
                        {{ ucfirst(str_replace('_', ' ', $child->getAgeGroup())) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2">
            <!-- View Calendar -->
            <a href="{{ route('children.show', $child->id) }}" 
               class="bg-green-100 text-green-700 p-2 rounded-lg hover:bg-green-200 transition"
               title="{{ __('view_schedule') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </a>

            <!-- Edit -->
            <button
                hx-get="{{ route('children.edit', $child->id) }}"
                hx-target="#child-form-modal"
                hx-swap="innerHTML"
                class="bg-blue-100 text-blue-700 p-2 rounded-lg hover:bg-blue-200 transition"
                title="{{ __('edit_child') }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>

            <!-- Delete -->
            <button
                hx-delete="{{ route('children.destroy', $child->id) }}"
                hx-target="#child-{{ $child->id }}"
                hx-swap="outerHTML"
                hx-confirm="{{ __('confirm_delete_child', ['name' => $child->name]) }}"
                class="bg-red-100 text-red-700 p-2 rounded-lg hover:bg-red-200 transition"
                title="{{ __('delete_child') }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
        <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">0</p>
            <p class="text-xs text-gray-600">{{ __('subjects') }}</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-green-600">0</p>
            <p class="text-xs text-gray-600">{{ __('units') }}</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-purple-600">0</p>
            <p class="text-xs text-gray-600">{{ __('time_blocks') }}</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-orange-600">0%</p>
            <p class="text-xs text-gray-600">{{ __('progress') }}</p>
        </div>
    </div>
</div>