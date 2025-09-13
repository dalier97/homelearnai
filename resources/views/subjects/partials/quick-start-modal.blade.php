<!-- Modal Overlay and Container -->
<div class="fixed inset-0 z-50 overflow-y-auto" data-testid="quick-start-modal">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40" onclick="document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden');"></div>
    
    <!-- Modal dialog -->
    <div class="flex min-h-screen items-center justify-center p-4 relative z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full relative z-50" onclick="event.stopPropagation()" data-testid="modal-content" x-data="quickStartModal()">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('quick_start_subjects') }}</h3>
                    <button 
                        onclick="document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden');"
                        type="button" 
                        class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4">
                <p class="mb-6 text-sm text-gray-600">{{ __('quick_start_description') }}</p>
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-700">
                        {{ __('creating_subjects_for') }} <strong>{{ $child->name }}</strong> ({{ __('grade') }} {{ $child->grade }})
                    </p>
                </div>

                <form 
                    hx-post="{{ route('subjects.quick-start.store') }}"
                    hx-target="#subjects-list"
                    hx-swap="innerHTML"
                    onsubmit="setTimeout(() => { document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden'); }, 100)"
                    class="space-y-6"
                >
                    @csrf
                    <input type="hidden" name="child_id" value="{{ $child->id }}">

                    <!-- Grade Level Selection -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            {{ __('select_grade_level') }}
                        </label>
                        <select name="grade_level" 
                                x-model="gradeLevel" 
                                @change="updateSubjects()"
                                required
                                class="w-full min-h-[42px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">{{ __('select_grade_level') }}</option>
                            <option value="elementary">{{ __('elementary_k5') }}</option>
                            <option value="middle">{{ __('middle_school_68') }}</option>
                            <option value="high">{{ __('high_school_912') }}</option>
                        </select>
                    </div>

                    <!-- Recommended Subjects -->
                    <div x-show="gradeLevel" x-cloak>
                        <h4 class="mb-3 text-sm font-medium text-gray-700">{{ __('recommended_subjects') }}</h4>
                        <p class="mb-3 text-xs text-gray-500">{{ __('select_subjects_to_add') }}</p>
                        
                        <div class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-3 bg-gray-50">
                            <template x-for="subject in getSubjectsForGrade()" :key="subject">
                                <label class="flex items-center p-2 rounded hover:bg-white cursor-pointer border border-transparent hover:border-gray-200 transition-colors">
                                    <input type="checkbox" 
                                           name="subjects[]" 
                                           :value="subject"
                                           :checked="selectedSubjects.includes(subject)"
                                           @change="toggleSubject(subject)"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700" x-text="subject"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Custom Subjects -->
                    <div x-show="gradeLevel" x-cloak>
                        <h4 class="mb-3 text-sm font-medium text-gray-700">{{ __('add_custom_subjects') }} <span class="text-xs text-gray-500">({{ __('optional') }})</span></h4>
                        <div class="space-y-2">
                            <template x-for="(custom, index) in customSubjects" :key="index">
                                <div class="flex items-center space-x-2">
                                    <input type="text" 
                                           name="custom_subjects[]"
                                           x-model="customSubjects[index]"
                                           :placeholder="'{{ __('custom_subject_placeholder') }}' + ' ' + (index + 1)"
                                           class="flex-1 min-h-[42px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button type="button" 
                                            @click="removeCustomSubject(index)"
                                            x-show="customSubjects.length > 1"
                                            class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" 
                                    @click="addCustomSubject()"
                                    x-show="customSubjects.length < 3"
                                    class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                + {{ __('add_custom_subjects') }}
                            </button>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-between pt-6 border-t">
                        <button 
                            onclick="document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden');"
                            type="button" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                            {{ __('skip_quick_start') }}
                        </button>
                        <button 
                            type="submit"
                            :disabled="!canSubmit()"
                            :class="canSubmit() ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                            class="px-6 py-2 text-sm font-medium rounded-lg transition-colors">
                            <span x-text="getSubmitText()"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function quickStartModal() {
    return {
        gradeLevel: '',
        selectedSubjects: [],
        customSubjects: [''],
        templates: @json($templates ?? []),
        
        updateSubjects() {
            // Pre-select all subjects when grade level is chosen
            if (this.gradeLevel) {
                this.selectedSubjects = [...this.getSubjectsForGrade()];
            } else {
                this.selectedSubjects = [];
            }
        },
        
        getSubjectsForGrade() {
            if (!this.gradeLevel || !this.templates[this.gradeLevel]) {
                return [];
            }
            return this.templates[this.gradeLevel];
        },
        
        toggleSubject(subject) {
            const index = this.selectedSubjects.indexOf(subject);
            if (index > -1) {
                this.selectedSubjects.splice(index, 1);
            } else {
                this.selectedSubjects.push(subject);
            }
        },
        
        addCustomSubject() {
            if (this.customSubjects.length < 3) {
                this.customSubjects.push('');
            }
        },
        
        removeCustomSubject(index) {
            this.customSubjects.splice(index, 1);
            if (this.customSubjects.length === 0) {
                this.customSubjects = [''];
            }
        },
        
        canSubmit() {
            // Must have grade level and at least one subject selected or custom subject entered
            if (!this.gradeLevel) return false;
            
            const hasSelectedSubjects = this.selectedSubjects.length > 0;
            const hasCustomSubjects = this.customSubjects.some(s => s.trim() !== '');
            
            return hasSelectedSubjects || hasCustomSubjects;
        },
        
        getSubmitText() {
            if (!this.canSubmit()) return '{{ __("create_selected_subjects") }}';
            
            const selectedCount = this.selectedSubjects.length;
            const customCount = this.customSubjects.filter(s => s.trim() !== '').length;
            const totalCount = selectedCount + customCount;
            
            if (totalCount === 1) {
                return '{{ __("create_1_subject") }}';
            } else {
                return `{{ __("create_count_subjects") }}`.replace(':count', totalCount);
            }
        }
    }
}
</script>