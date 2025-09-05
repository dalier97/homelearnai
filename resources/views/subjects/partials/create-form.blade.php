<!-- Modal Overlay and Container -->
<div class="fixed inset-0 z-40 overflow-y-auto" data-testid="subject-modal">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40" onclick="document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden');"></div>
    
    <!-- Modal dialog -->
    <div class="flex min-h-screen items-center justify-center p-4 relative z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full relative z-50" onclick="event.stopPropagation()" data-testid="modal-content">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Add New Subject</h3>
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
                <form 
                    hx-post="{{ route('subjects.store') }}"
                    hx-target="#subjects-list"
                    hx-swap="innerHTML"
                    onsubmit="setTimeout(() => { document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden'); }, 100)"
                    class="space-y-4"
                >
                    @csrf
                    
                    <!-- Subject Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            placeholder="{{ __('subject_name_example') }}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <!-- Subject Color -->
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <select 
                            name="color" 
                            id="color"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Choose a color...</option>
                            @foreach($colors as $value => $label)
                                <option value="{{ $value }}" style="color: {{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button 
                            onclick="document.getElementById('subject-modal').innerHTML = ''; document.getElementById('subject-modal').classList.add('hidden');"
                            type="button" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>