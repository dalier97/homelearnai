<!-- Standalone Unit Creation Form (no modal) -->
<div class="p-6">
    <!-- Form -->
    <form data-testid="unit-form"
          action="{{ route('subjects.units.store', $subject->id) }}"
          method="POST">
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
                placeholder="{{ __('unit_name_example') }}"
                value="{{ old('name') }}">
            @error('name')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
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
                placeholder="{{ __('unit_description_placeholder') }}">{{ old('description') }}</textarea>
            @error('description')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Target Completion Date -->
        <div class="mb-6">
            <label for="target_completion_date" class="block text-sm font-medium text-gray-700 mb-2">Target Completion Date (Optional)</label>
            <input 
                type="date" 
                id="target_completion_date" 
                name="target_completion_date" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                value="{{ old('target_completion_date') }}">
            @error('target_completion_date')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <a 
                href="{{ route('subjects.show', $subject->id) }}"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                Cancel
            </a>
            <button 
                type="submit"
                data-testid="save-unit-button"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                Save Unit
            </button>
        </div>
    </form>
</div>