<!-- Import Error Display -->
<div class="bg-red-50 border border-red-200 rounded-lg p-6">
    <div class="flex items-center mb-4">
        <svg class="w-8 h-8 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <div>
            <h3 class="text-lg font-medium text-red-800">Import Error</h3>
            <p class="text-sm text-red-600 mt-1">There was a problem processing your import file.</p>
        </div>
    </div>

    <div class="bg-white border border-red-200 rounded p-4 mb-4">
        <p class="text-sm text-gray-800 font-mono">{{ $error }}</p>
    </div>

    <div class="bg-red-100 border border-red-300 rounded p-3">
        <h4 class="text-sm font-medium text-red-800 mb-2">Common Issues & Solutions:</h4>
        <ul class="text-sm text-red-700 space-y-1">
            <li>• <strong>File format:</strong> Ensure your file has the correct extension (.apkg, .mem, .csv, etc.)</li>
            <li>• <strong>File size:</strong> Files must be smaller than 100MB for Anki packages, 10MB for others</li>
            <li>• <strong>File corruption:</strong> Try re-exporting from the original application</li>
            <li>• <strong>Anki packages:</strong> Ensure the .apkg file was exported from Anki correctly</li>
            <li>• <strong>CSV format:</strong> Check that columns are properly separated (comma, tab, or dash)</li>
        </ul>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
        <button type="button" 
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            @click="$event.target.closest('.fixed').remove()">
            Close
        </button>
        <button type="button"
            class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
            onclick="location.reload()">
            Try Again
        </button>
    </div>
</div>