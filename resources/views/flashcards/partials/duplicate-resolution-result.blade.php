<!-- Duplicate Resolution Result -->
<div class="space-y-4">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center mb-3">
            <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <h4 class="text-lg font-medium text-green-800">Duplicates Resolved Successfully</h4>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
            <div>
                <span class="font-medium text-green-800">Skipped:</span>
                <p class="text-green-700 font-semibold">{{ $mergeResult['results']['skipped'] }}</p>
            </div>
            <div>
                <span class="font-medium text-green-800">Updated:</span>
                <p class="text-green-700 font-semibold">{{ $mergeResult['results']['updated'] }}</p>
            </div>
            <div>
                <span class="font-medium text-green-800">Kept Both:</span>
                <p class="text-green-700 font-semibold">{{ $mergeResult['results']['kept_both'] }}</p>
            </div>
            <div>
                <span class="font-medium text-green-800">Replaced:</span>
                <p class="text-green-700 font-semibold">{{ $mergeResult['results']['replaced'] }}</p>
            </div>
        </div>

        @if(!empty($mergeResult['results']['errors']))
        <div class="border-t border-green-200 pt-3 mt-3">
            <h5 class="text-sm font-medium text-green-800 mb-2">Errors Encountered:</h5>
            <ul class="text-sm text-green-700 space-y-1">
                @foreach($mergeResult['results']['errors'] as $error)
                <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="text-lg font-medium text-blue-800 mb-3">Ready for Final Import</h4>
        <p class="text-sm text-blue-700 mb-4">
            Duplicates have been resolved. The remaining unique cards are ready to be imported.
        </p>

        <form 
            hx-post="{{ route('flashcards.execute-advanced-import', $unit->id) }}"
            hx-target="#flashcard-list"
            class="flex justify-end space-x-3">
            @csrf
            <input type="hidden" name="filename" value="{{ $filename }}">
            <input type="hidden" name="duplicates_resolved" value="1">
            
            <button type="button" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                @click="$event.target.closest('.fixed').remove()">
                Cancel
            </button>
            <button type="submit"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                Complete Import
            </button>
        </form>
    </div>
</div>