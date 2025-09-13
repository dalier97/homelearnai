<!-- Import Flashcards Modal -->
<div id="flashcard-modal-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4" data-testid="flashcard-modal" x-data="{ open: true }" x-show="open">
    <div id="flashcard-import-modal-content" class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto relative z-50" data-testid="modal-content">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Import Flashcards</h3>
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
        <div class="p-6" id="import-content">
            <!-- Import Method Selection -->
            <div class="mb-6">
                <label class="text-sm font-medium text-gray-700 mb-3 block">Import Method</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="import_method" value="file" class="mr-2" checked>
                        <span class="text-sm">Upload File</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="import_method" value="paste" class="mr-2">
                        <span class="text-sm">Copy & Paste</span>
                    </label>
                </div>
            </div>

            <!-- File Upload Section -->
            <div id="file-import-section" class="mb-6">
                <form hx-post="{{ route('units.flashcards.import.preview', $unit->id) }}" 
                      hx-target="#import-content" 
                      hx-swap="innerHTML"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="import_method" value="file">
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <div class="mt-4">
                            <label for="import_file" class="cursor-pointer">
                                <span class="mt-2 block text-sm font-medium text-gray-900">
                                    Click to upload a file or drag and drop
                                </span>
                                <span class="mt-1 block text-sm text-gray-600">
                                    Supported formats: {{ implode(', ', array_map('strtoupper', $supportedExtensions)) }}
                                </span>
                                <span class="mt-1 block text-xs text-gray-500">
                                    Maximum {{ $maxImportSize }} flashcards, 5MB file limit
                                </span>
                            </label>
                            <input id="import_file" 
                                   name="import_file" 
                                   type="file" 
                                   class="sr-only"
                                   accept=".{{ implode(',.' , $supportedExtensions) }}"
                                   onchange="updateFileName(this)">
                        </div>
                        <p id="selected-file-name" class="mt-2 text-sm text-gray-600 hidden"></p>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md transition-colors">
                            Preview Import
                        </button>
                    </div>
                </form>
            </div>

            <!-- Copy & Paste Section -->
            <div id="paste-import-section" class="mb-6 hidden">
                <form hx-post="{{ route('units.flashcards.import.preview', $unit->id) }}" 
                      hx-target="#import-content" 
                      hx-swap="innerHTML">
                    @csrf
                    <input type="hidden" name="import_method" value="paste">
                    
                    <div class="mb-4">
                        <label for="import_text" class="block text-sm font-medium text-gray-700 mb-2">
                            Paste your flashcard data
                        </label>
                        <textarea 
                            id="import_text" 
                            name="import_text" 
                            rows="10" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                            placeholder="Paste your flashcards here. Supported formats:&#10;&#10;Question[TAB]Answer&#10;Question[TAB]Answer[TAB]Hint&#10;&#10;Question,Answer&#10;Question,Answer,Hint&#10;&#10;Question - Answer&#10;Question - Answer - Hint"></textarea>
                        <div class="mt-2 text-sm text-gray-600">
                            <p><strong>Supported formats:</strong></p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li><strong>Quizlet format:</strong> Question[TAB]Answer (copy directly from Quizlet export)</li>
                                <li><strong>CSV format:</strong> Question,Answer,Hint</li>
                                <li><strong>Dash format:</strong> Question - Answer - Hint</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md transition-colors">
                            Preview Import
                        </button>
                    </div>
                </form>
            </div>

            <!-- Format Examples -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Format Examples</h4>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Quizlet Export (Tab-separated):</p>
                        <code class="text-xs bg-white p-2 block rounded border mt-1">What is the capital of France?[TAB]Paris</code>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">CSV Format:</p>
                        <code class="text-xs bg-white p-2 block rounded border mt-1">What is the capital of France?,Paris,The City of Light</code>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Dash Separated:</p>
                        <code class="text-xs bg-white p-2 block rounded border mt-1">What is the capital of France? - Paris - The City of Light</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle between import methods
document.addEventListener('change', function(e) {
    if (e.target.name === 'import_method') {
        const fileSection = document.getElementById('file-import-section');
        const pasteSection = document.getElementById('paste-import-section');
        
        if (e.target.value === 'file') {
            fileSection.classList.remove('hidden');
            pasteSection.classList.add('hidden');
        } else {
            fileSection.classList.add('hidden');
            pasteSection.classList.remove('hidden');
        }
    }
});

// Update file name display
function updateFileName(input) {
    const fileNameDisplay = document.getElementById('selected-file-name');
    if (input.files && input.files[0]) {
        fileNameDisplay.textContent = `Selected: ${input.files[0].name}`;
        fileNameDisplay.classList.remove('hidden');
    } else {
        fileNameDisplay.classList.add('hidden');
    }
}

// Drag and drop functionality
const dropZone = document.querySelector('.border-dashed');
const fileInput = document.getElementById('import_file');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-blue-400', 'bg-blue-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        updateFileName(fileInput);
    }
}

// Close modal when clicking outside (handled by Alpine.js click away)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('fixed') && e.target.hasAttribute('data-testid') && e.target.getAttribute('data-testid') === 'import-modal') {
        e.target.remove();
    }
});
</script>