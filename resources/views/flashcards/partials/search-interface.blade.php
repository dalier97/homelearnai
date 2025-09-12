{{-- Enhanced Flashcard Search Interface --}}
@props(['unit', 'filters' => []])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6" id="flashcard-search-interface">
    {{-- Search Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Search Flashcards</h3>
        <button 
            type="button" 
            id="toggle-advanced-search"
            class="text-sm text-blue-600 hover:text-blue-800 transition-colors"
        >
            <span class="advanced-search-toggle">Advanced Search</span>
        </button>
    </div>

    {{-- Quick Search Bar --}}
    <div class="relative mb-4">
        <div class="relative">
            <input 
                type="text" 
                id="flashcard-search"
                placeholder="Search flashcards by question, answer, or hint..."
                class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                hx-get="{{ route('flashcards.search', $unit->id) }}"
                hx-trigger="keyup changed delay:300ms"
                hx-target="#search-results"
                hx-swap="innerHTML"
                hx-indicator="#search-loading"
                hx-include="#search-filters"
                autocomplete="off"
            >
            
            {{-- Search Icon --}}
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            {{-- Loading Indicator --}}
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center" id="search-loading" style="display: none;">
                <x-loading-spinner size="small" color="gray" />
            </div>
            
            {{-- Clear Button --}}
            <button 
                type="button" 
                id="clear-search"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                style="display: none;"
                onclick="clearSearch()"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        {{-- Search Suggestions Dropdown --}}
        <div id="search-suggestions" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden">
            {{-- Suggestions will be loaded here --}}
        </div>
    </div>

    {{-- Quick Filter Pills --}}
    <div class="flex flex-wrap gap-2 mb-4" id="quick-filters">
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="card_type" 
            data-value="basic"
        >
            Basic Cards
        </button>
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="card_type" 
            data-value="multiple_choice"
        >
            Multiple Choice
        </button>
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="difficulty" 
            data-value="easy"
        >
            Easy
        </button>
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="difficulty" 
            data-value="hard"
        >
            Hard
        </button>
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="has_images" 
            data-value="true"
        >
            With Images
        </button>
        <button 
            type="button" 
            class="filter-pill" 
            data-filter="has_hints" 
            data-value="true"
        >
            With Hints
        </button>
    </div>

    {{-- Advanced Search Panel (Hidden by default) --}}
    <div id="advanced-search-panel" class="hidden border-t border-gray-200 pt-4">
        <form id="search-filters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Card Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Card Type</label>
                <select name="card_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="basic">Basic</option>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="cloze">Cloze</option>
                    <option value="typed_answer">Typed Answer</option>
                    <option value="image_occlusion">Image Occlusion</option>
                </select>
            </div>

            {{-- Difficulty Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Difficulty</label>
                <select name="difficulty" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Difficulties</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            {{-- Tag Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tag</label>
                <input 
                    type="text" 
                    name="tag"
                    placeholder="Enter tag name..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Date Range --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Created After</label>
                <input 
                    type="date" 
                    name="date_from"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Created Before</label>
                <input 
                    type="date" 
                    name="date_to"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Additional Filters --}}
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="has_images" value="true" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Has Images</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="has_hints" value="true" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Has Hints</span>
                </label>
            </div>
        </form>

        {{-- Advanced Search Actions --}}
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
            <button 
                type="button" 
                onclick="clearAllFilters()"
                class="text-sm text-gray-600 hover:text-gray-800 transition-colors"
            >
                Clear All Filters
            </button>
            
            <button 
                type="button"
                hx-get="{{ route('flashcards.search', $unit->id) }}"
                hx-target="#search-results"
                hx-swap="innerHTML"
                hx-indicator="#search-loading"
                hx-include="#search-filters,#flashcard-search"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                Apply Filters
            </button>
        </div>
    </div>

    {{-- Search Statistics --}}
    <div id="search-stats" class="hidden border-t border-gray-100 pt-3 mt-3">
        <div class="flex items-center justify-between text-sm text-gray-600">
            <span id="results-count">0 results</span>
            <span id="search-time"></span>
        </div>
    </div>
</div>

{{-- Search Results Container --}}
<div id="search-results">
    {{-- Results will be loaded here --}}
</div>

<style>
    .filter-pill {
        @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border transition-all;
        @apply border-gray-300 text-gray-700 bg-white hover:bg-gray-50;
    }
    
    .filter-pill.active {
        @apply border-blue-500 text-blue-700 bg-blue-50;
    }
    
    .suggestion-item {
        @apply px-4 py-2 hover:bg-gray-50 cursor-pointer text-sm border-b border-gray-100 last:border-b-0;
    }
    
    .suggestion-item:hover {
        @apply bg-blue-50;
    }
</style>

<script>
    // Search interface functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('flashcard-search');
        const clearButton = document.getElementById('clear-search');
        const suggestionsDiv = document.getElementById('search-suggestions');
        const advancedPanel = document.getElementById('advanced-search-panel');
        const toggleButton = document.getElementById('toggle-advanced-search');
        const searchStats = document.getElementById('search-stats');
        
        // Toggle advanced search
        toggleButton.addEventListener('click', function() {
            const isHidden = advancedPanel.classList.contains('hidden');
            advancedPanel.classList.toggle('hidden');
            
            const toggle = this.querySelector('.advanced-search-toggle');
            toggle.textContent = isHidden ? 'Simple Search' : 'Advanced Search';
        });
        
        // Show/hide clear button and search suggestions
        searchInput.addEventListener('input', function() {
            const hasValue = this.value.length > 0;
            clearButton.style.display = hasValue ? 'flex' : 'none';
            
            // Load suggestions for queries longer than 2 characters
            if (this.value.length >= 2) {
                loadSearchSuggestions(this.value);
            } else {
                hideSuggestions();
            }
        });
        
        // Quick filter pills
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                this.classList.toggle('active');
                
                const filter = this.dataset.filter;
                const value = this.dataset.value;
                const isActive = this.classList.contains('active');
                
                // Update form fields
                const form = document.getElementById('search-filters');
                const field = form.querySelector(`[name="${filter}"]`);
                
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = isActive;
                    } else {
                        field.value = isActive ? value : '';
                    }
                }
                
                // Trigger search
                triggerSearch();
            });
        });
        
        // Filter form changes
        document.getElementById('search-filters').addEventListener('change', function() {
            triggerSearch();
        });
        
        // Click outside to close suggestions
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                hideSuggestions();
            }
        });
        
        // HTMX response handler for search results
        document.body.addEventListener('htmx:afterRequest', function(evt) {
            if (evt.detail.target.id === 'search-results') {
                updateSearchStats(evt.detail.xhr.response);
            }
        });
    });
    
    function clearSearch() {
        const searchInput = document.getElementById('flashcard-search');
        const clearButton = document.getElementById('clear-search');
        
        searchInput.value = '';
        clearButton.style.display = 'none';
        hideSuggestions();
        
        // Trigger search to show all results
        triggerSearch();
    }
    
    function clearAllFilters() {
        const form = document.getElementById('search-filters');
        const searchInput = document.getElementById('flashcard-search');
        
        // Clear form
        form.reset();
        
        // Clear search input
        searchInput.value = '';
        
        // Remove active pills
        document.querySelectorAll('.filter-pill.active').forEach(pill => {
            pill.classList.remove('active');
        });
        
        // Trigger search
        triggerSearch();
    }
    
    function triggerSearch() {
        const searchInput = document.getElementById('flashcard-search');
        
        // Trigger HTMX request
        htmx.trigger(searchInput, 'keyup');
    }
    
    function loadSearchSuggestions(query) {
        // This would make an AJAX call to get suggestions
        // For now, we'll implement a simple client-side version
        const suggestions = [
            'mathematics',
            'algebra',
            'geometry',
            'history',
            'science'
        ].filter(term => term.toLowerCase().includes(query.toLowerCase()));
        
        showSuggestions(suggestions);
    }
    
    function showSuggestions(suggestions) {
        const suggestionsDiv = document.getElementById('search-suggestions');
        
        if (suggestions.length === 0) {
            hideSuggestions();
            return;
        }
        
        const html = suggestions.map(suggestion => 
            `<div class="suggestion-item" onclick="selectSuggestion('${suggestion}')">${suggestion}</div>`
        ).join('');
        
        suggestionsDiv.innerHTML = html;
        suggestionsDiv.classList.remove('hidden');
    }
    
    function hideSuggestions() {
        const suggestionsDiv = document.getElementById('search-suggestions');
        suggestionsDiv.classList.add('hidden');
    }
    
    function selectSuggestion(suggestion) {
        const searchInput = document.getElementById('flashcard-search');
        searchInput.value = suggestion;
        hideSuggestions();
        triggerSearch();
    }
    
    function updateSearchStats(response) {
        const searchStats = document.getElementById('search-stats');
        const resultsCount = document.getElementById('results-count');
        const searchTime = document.getElementById('search-time');
        
        // Try to extract stats from response (this would depend on your actual response format)
        try {
            const stats = extractStatsFromResponse(response);
            resultsCount.textContent = `${stats.count} result${stats.count !== 1 ? 's' : ''}`;
            searchTime.textContent = `${stats.time}ms`;
            searchStats.classList.remove('hidden');
        } catch (e) {
            searchStats.classList.add('hidden');
        }
    }
    
    function extractStatsFromResponse(response) {
        // This is a placeholder - implement based on your actual response format
        const count = (response.match(/flashcard-item/g) || []).length;
        return { count, time: Math.random() * 200 + 50 };
    }
</script>