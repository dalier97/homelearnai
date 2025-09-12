<!-- Modal Overlay -->
<div class="fixed inset-0 z-40 overflow-y-auto" 
     data-testid="unit-create-modal"
     id="unit-modal-overlay"
     style="display: block;">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40" onclick="document.getElementById('unit-modal-overlay').remove();"></div>
    
    <!-- Modal dialog -->
    <div class="flex min-h-screen items-center justify-center p-4 relative z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md relative z-50" data-testid="modal-content" onclick="event.stopPropagation();">
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Add New Unit</h3>
            <button type="button" onclick="document.getElementById('unit-modal-overlay').remove();" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <form data-testid="unit-form"
              hx-post="{{ route('subjects.units.store', $subject->id) }}" 
              hx-target="#units-list"
              hx-swap="innerHTML"
              hx-on::after-request="if(event.detail.successful) { document.getElementById('unit-modal-overlay').remove(); }">
            @csrf
            
            <!-- Unit Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Unit Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    data-testid="unit-name-input"
                    required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('unit_name_example') }}">
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    data-testid="unit-description-input"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('unit_description_placeholder') }}"></textarea>
            </div>

            <!-- Target Completion Date -->
            <div class="mb-6">
                <label for="target_completion_date" class="block text-sm font-medium text-gray-700 mb-2">Target Completion Date (Optional)</label>
                <input 
                    type="date" 
                    id="target_completion_date" 
                    name="target_completion_date" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="document.getElementById('unit-modal-overlay').remove();"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button 
                    type="submit"
                    data-testid="save-unit-button"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    Save Unit
                </button>
            </div>
        </form>
        </div>
    </div>
</div>