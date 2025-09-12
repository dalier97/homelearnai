<!-- Advanced Import Flashcards Modal -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 flex items-center justify-center p-4" x-data="{ open: true }" x-show="open" data-testid="advanced-import-modal">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto relative z-50" data-testid="modal-content">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Advanced Import Flashcards</h3>
                <p class="text-sm text-gray-500 mt-1">Import from Anki packages, Mnemosyne, or enhanced CSV files</p>
            </div>
            <button 
                type="button"
                class="text-gray-400 hover:text-gray-600 p-1"
                @click="$event.target.closest('.fixed').remove()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6" id="advanced-import-content">
            <form 
                hx-post="{{ route('flashcards.preview-advanced-import', $unit->id) }}" 
                hx-target="#advanced-import-content"
                hx-encoding="multipart/form-data"
                enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <!-- Supported Formats Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Supported Import Formats</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @foreach($supportedFormats as $ext => $description)
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ in_array($ext, ['apkg']) ? 'bg-green-100 text-green-800' : 
                                       (in_array($ext, ['mem', 'xml']) ? 'bg-purple-100 text-purple-800' : 
                                        'bg-gray-100 text-gray-800') }}">
                                    .{{ $ext }}
                                </span>
                                <span class="text-gray-700">{{ $description }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <label for="import_file" class="block text-sm font-medium text-gray-700 mb-2">
                        Select File to Import
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-400 transition-colors">
                        <input 
                            type="file" 
                            id="import_file" 
                            name="import_file" 
                            accept=".apkg,.mem,.xml,.csv,.tsv,.txt"
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                                cursor-pointer"
                            required>
                        <p class="text-xs text-gray-500 mt-2">
                            Maximum file size: 100MB. Anki packages may contain media files.
                        </p>
                    </div>
                </div>

                <!-- Advanced Options -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-900">Import Options</h4>
                    
                    <!-- Duplicate Detection -->
                    <label class="flex items-start space-x-3">
                        <input 
                            type="checkbox" 
                            name="detect_duplicates" 
                            value="1"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Detect Duplicate Cards</span>
                            <p class="text-xs text-gray-500">Check for similar questions and provide merge options</p>
                        </div>
                    </label>

                    <!-- Media Handling -->
                    <label class="flex items-start space-x-3">
                        <input 
                            type="checkbox" 
                            name="import_options[handle_media]" 
                            value="1"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            checked>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Import Media Files</span>
                            <p class="text-xs text-gray-500">Extract and store images/audio from packages (Anki only)</p>
                        </div>
                    </label>

                    <!-- Advanced Card Types -->
                    <label class="flex items-start space-x-3">
                        <input 
                            type="checkbox" 
                            name="import_options[preserve_types]" 
                            value="1"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            checked>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Preserve Advanced Card Types</span>
                            <p class="text-xs text-gray-500">Import cloze, multiple choice, and image occlusion cards</p>
                        </div>
                    </label>

                    <!-- Import History -->
                    <label class="flex items-start space-x-3">
                        <input 
                            type="checkbox" 
                            name="import_options[track_history]" 
                            value="1"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            checked>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Track Import History</span>
                            <p class="text-xs text-gray-500">Save import details and enable rollback capability</p>
                        </div>
                    </label>
                </div>

                <!-- Progress Bar (initially hidden) -->
                <div id="import-progress" class="hidden">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Processing file...</p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button 
                        type="button" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        @click="$event.target.closest('.fixed').remove()">
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        hx-indicator="#import-progress">
                        Preview Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('htmx:beforeRequest', function(evt) {
    if (evt.detail.elt.matches('form[hx-post*="preview-advanced-import"]')) {
        const progress = document.getElementById('import-progress');
        if (progress) {
            progress.classList.remove('hidden');
        }
    }
});

document.addEventListener('htmx:afterRequest', function(evt) {
    if (evt.detail.elt.matches('form[hx-post*="preview-advanced-import"]')) {
        const progress = document.getElementById('import-progress');
        if (progress) {
            progress.classList.add('hidden');
        }
    }
});
</script>