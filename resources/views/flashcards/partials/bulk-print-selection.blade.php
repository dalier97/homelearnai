<!-- Bulk Print Selection Interface -->
<div class="bulk-selection-interface">
    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-600">
            Select specific flashcards to print ({{ $flashcards->total() }} total)
        </div>
        <div class="space-x-2">
            <button type="button" onclick="selectAllCards()" 
                    class="text-xs text-blue-600 hover:text-blue-700 focus:outline-none">
                Select All
            </button>
            <button type="button" onclick="deselectAllCards()" 
                    class="text-xs text-gray-600 hover:text-gray-700 focus:outline-none">
                Deselect All
            </button>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg bg-white max-h-80 overflow-y-auto">
        @if($flashcards->count() > 0)
            @foreach($flashcards as $flashcard)
                <div class="flex items-start p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                    <label class="flex items-start cursor-pointer w-full">
                        <input type="checkbox" 
                               name="selected_cards[]" 
                               value="{{ $flashcard->id }}" 
                               class="mt-1 mr-3 text-blue-600 focus:ring-blue-500 card-checkbox" 
                               checked
                               onchange="updateSelectedCount()">
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <!-- Card Type Badge -->
                                @php
                                    $typeColors = [
                                        'basic' => 'bg-blue-100 text-blue-800',
                                        'multiple_choice' => 'bg-green-100 text-green-800', 
                                        'true_false' => 'bg-yellow-100 text-yellow-800',
                                        'cloze' => 'bg-purple-100 text-purple-800',
                                        'typed_answer' => 'bg-indigo-100 text-indigo-800',
                                        'image_occlusion' => 'bg-pink-100 text-pink-800'
                                    ];
                                @endphp
                                
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$flashcard->card_type] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $flashcard->card_type)) }}
                                </span>
                                
                                <!-- Difficulty Badge -->
                                @php
                                    $difficultyColors = [
                                        'easy' => 'bg-green-100 text-green-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'hard' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $difficultyColors[$flashcard->difficulty_level] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($flashcard->difficulty_level) }}
                                </span>
                            </div>
                            
                            <div class="text-sm font-medium text-gray-900 mb-1">
                                Q: {{ Str::limit(strip_tags($flashcard->question), 120) }}
                            </div>
                            
                            <div class="text-xs text-gray-600 mb-1">
                                A: {{ Str::limit(strip_tags($flashcard->answer), 80) }}
                            </div>
                            
                            @if($flashcard->hint)
                                <div class="text-xs text-gray-500 italic">
                                    Hint: {{ Str::limit(strip_tags($flashcard->hint), 60) }}
                                </div>
                            @endif
                            
                            @if(!empty($flashcard->tags))
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($flashcard->tags as $tag)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                                            #{{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </label>
                </div>
            @endforeach
        @else
            <div class="p-8 text-center text-gray-500">
                No flashcards available for selection.
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($flashcards->hasPages())
        <div class="mt-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ $flashcards->firstItem() }} to {{ $flashcards->lastItem() }} of {{ $flashcards->total() }} results
                </div>
                <div class="flex space-x-1">
                    @if($flashcards->onFirstPage())
                        <span class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md">
                            Previous
                        </span>
                    @else
                        <button type="button" 
                                onclick="loadSelectionPage('{{ $flashcards->previousPageUrl() }}')"
                                class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Previous
                        </button>
                    @endif

                    @foreach($flashcards->getUrlRange(max(1, $flashcards->currentPage() - 2), min($flashcards->lastPage(), $flashcards->currentPage() + 2)) as $page => $url)
                        @if($page == $flashcards->currentPage())
                            <span class="px-3 py-1 text-sm text-white bg-blue-600 border border-blue-600 rounded-md">
                                {{ $page }}
                            </span>
                        @else
                            <button type="button" 
                                    onclick="loadSelectionPage('{{ $url }}')"
                                    class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach

                    @if($flashcards->hasMorePages())
                        <button type="button" 
                                onclick="loadSelectionPage('{{ $flashcards->nextPageUrl() }}')"
                                class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Next
                        </button>
                    @else
                        <span class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md">
                            Next
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Filter Options -->
    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-700 font-medium mb-2">Quick Filters</div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="filterByType('basic')" 
                    class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200 focus:outline-none">
                Basic Cards Only
            </button>
            <button type="button" onclick="filterByType('multiple_choice')" 
                    class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200 focus:outline-none">
                Multiple Choice Only
            </button>
            <button type="button" onclick="filterByDifficulty('hard')" 
                    class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200 focus:outline-none">
                Hard Cards Only
            </button>
            <button type="button" onclick="clearFilters()" 
                    class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded hover:bg-gray-200 focus:outline-none">
                Clear Filters
            </button>
        </div>
    </div>
</div>

<script>
function selectAllCards() {
    document.querySelectorAll('.card-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function deselectAllCards() {
    document.querySelectorAll('.card-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.card-checkbox:checked').length;
    updateSelectedCount(selectedCount);
}

function filterByType(cardType) {
    document.querySelectorAll('.card-checkbox').forEach(checkbox => {
        const cardContainer = checkbox.closest('div').querySelector('.text-sm');
        if (cardContainer && cardContainer.textContent.toLowerCase().includes(cardType.replace('_', ' '))) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });
    updateSelectedCount();
}

function filterByDifficulty(difficulty) {
    document.querySelectorAll('.card-checkbox').forEach(checkbox => {
        const cardContainer = checkbox.closest('div').querySelector('.text-sm');
        if (cardContainer && cardContainer.textContent.toLowerCase().includes(difficulty)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });
    updateSelectedCount();
}

function clearFilters() {
    selectAllCards();
}

function loadSelectionPage(url) {
    // This would typically use HTMX to load the new page
    // For now, we'll just show a message
    alert('Pagination would load: ' + url);
}

// Initialize count
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>