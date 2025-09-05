<div x-data="{ open: true }" 
     x-show="open"
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <!-- Backdrop -->
        <div @click="open = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
            <form hx-post="{{ isset($child->id) ? route('children.update', $child->id) : route('children.store') }}"
                  hx-target="{{ isset($child->id) ? '#child-' . $child->id : '#children-list' }}"
                  hx-swap="{{ isset($child->id) ? 'outerHTML' : 'innerHTML' }}"
                  @submit="open = false">
                @if(isset($child->id))
                    @method('PUT')
                @endif
                @csrf
                
                <div class="bg-white px-6 pt-5 pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ isset($child->id) ? __('edit_child') : __('add_new_child') }}
                        </h3>
                        <button @click="open = false" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">{{ __('childs_name') }}</label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name"
                                value="{{ old('name', $child->name ?? '') }}"
                                required
                                placeholder="{{ __('enter_childs_full_name') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Age -->
                        <div>
                            <label for="age" class="block text-sm font-medium text-gray-700">{{ __('age') }}</label>
                            <select 
                                name="age" 
                                id="age"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('select_age') }}</option>
                                @for($i = 3; $i <= 25; $i++)
                                    <option value="{{ $i }}" {{ (old('age', $child->age ?? '') == $i) ? 'selected' : '' }}>
                                        {{ __('years_old', ['age' => $i]) }}
                                    </option>
                                @endfor
                            </select>
                            @error('age')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Age Group Info -->
                        @if(isset($child->age))
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm">
                                    <p class="text-blue-800 font-medium">
                                        {{ __('age_group', ['group' => ucfirst(str_replace('_', ' ', $child->getAgeGroup()))]) }}
                                    </p>
                                    <p class="text-blue-600 mt-1">
                                        {{ __('this_will_help_us_suggest_ageappropriate_curriculum_and_learning_activities') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Independence Level -->
                        <div>
                            <label for="independence_level" class="block text-sm font-medium text-gray-700">{{ __('independence_level') }}</label>
                            <select 
                                name="independence_level" 
                                id="independence_level"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="1" {{ (old('independence_level', $child->independence_level ?? 1) == 1) ? 'selected' : '' }}>
                                    {{ __('level_1_guided_view_only') }}
                                </option>
                                <option value="2" {{ (old('independence_level', $child->independence_level ?? 1) == 2) ? 'selected' : '' }}>
                                    {{ __('level_2_basic_can_reorder_tasks') }}
                                </option>
                                <option value="3" {{ (old('independence_level', $child->independence_level ?? 1) == 3) ? 'selected' : '' }}>
                                    {{ __('level_3_intermediate_move_sessions_in_week') }}
                                </option>
                                <option value="4" {{ (old('independence_level', $child->independence_level ?? 1) == 4) ? 'selected' : '' }}>
                                    {{ __('level_4_advanced_propose_weekly_plans') }}
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('controls_what_your_child_can_do_independently_in_their_learning_interface') }}
                            </p>
                            @error('independence_level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Future fields can be added here -->
                        <!-- Grade Level, Learning Style, Special Needs, etc. -->
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
                        @if(isset($child->id))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>{{ __('update_child') }}</span>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span>{{ __('add_child') }}</span>
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>