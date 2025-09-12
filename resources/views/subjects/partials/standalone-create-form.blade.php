<div class="p-6">
    <form action="{{ route('subjects.store') }}" method="POST" class="space-y-4">
        @csrf
        @if(isset($childId) && $childId)
            <input type="hidden" name="child_id" value="{{ $childId }}">
        @endif
        
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
                value="{{ old('name') }}"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
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
                    <option value="{{ $value }}" style="color: {{ $value }}" {{ old('color') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Child ID (if not passed as hidden field, show selector) -->
        @if(!isset($childId) || !$childId)
            <div>
                <label for="child_id" class="block text-sm font-medium text-gray-700 mb-1">Child</label>
                <select 
                    name="child_id" 
                    id="child_id"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Choose a child...</option>
                    @if(auth()->check())
                        @foreach(auth()->user()->children as $child)
                            <option value="{{ $child->id }}" {{ old('child_id') == $child->id ? 'selected' : '' }}>
                                {{ $child->name }} ({{ $child->grade }} {{ __('Grade') }})
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('child_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a 
                href="{{ route('subjects.index') }}" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                Save
            </button>
        </div>
    </form>
</div>