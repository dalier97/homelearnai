<!-- Advanced Import Preview -->
<div class="space-y-6">
    <!-- File Information -->
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-medium text-gray-900">Import Preview</h4>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {{ $typeInfo['import_type'] === 'anki' ? 'bg-green-100 text-green-800' : 
                       ($typeInfo['import_type'] === 'mnemosyne' ? 'bg-purple-100 text-purple-800' : 
                        'bg-blue-100 text-blue-800') }}">
                    {{ ucfirst($typeInfo['import_type']) }} Import
                </span>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">File:</span>
                <p class="text-gray-600 break-all">{{ $filename }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700">Size:</span>
                <p class="text-gray-600">{{ number_format($fileSize / 1024, 1) }} KB</p>
            </div>
            <div>
                <span class="font-medium text-gray-700">Cards Found:</span>
                <p class="text-gray-600 font-semibold">{{ count($parseResult['cards']) }}</p>
            </div>
            @if(isset($parseResult['media_count']) && $parseResult['media_count'] > 0)
            <div>
                <span class="font-medium text-gray-700">Media Files:</span>
                <p class="text-gray-600 font-semibold">{{ $parseResult['media_count'] }}</p>
            </div>
            @endif
        </div>

        @if($typeInfo['import_type'] === 'anki' && isset($parseResult['deck_info']))
        <div class="mt-4 pt-4 border-t border-gray-200">
            <h5 class="font-medium text-gray-700 mb-2">Anki Deck Information</h5>
            <div class="space-y-2">
                @foreach($parseResult['deck_info'] as $deckId => $deck)
                <div class="text-sm">
                    <span class="font-medium">{{ $deck['name'] }}</span>
                    @if($deck['description'])
                    <p class="text-gray-600 ml-2">{{ $deck['description'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Duplicate Detection Results -->
    @if($duplicateResult)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center mb-3">
            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <h4 class="text-lg font-medium text-yellow-800">Duplicate Cards Detected</h4>
        </div>
        
        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
            <div>
                <span class="font-medium text-yellow-800">Duplicates:</span>
                <p class="text-yellow-700 font-semibold">{{ $duplicateResult['duplicate_count'] }}</p>
            </div>
            <div>
                <span class="font-medium text-yellow-800">Unique:</span>
                <p class="text-yellow-700 font-semibold">{{ $duplicateResult['unique_count'] }}</p>
            </div>
            <div>
                <span class="font-medium text-yellow-800">Total:</span>
                <p class="text-yellow-700 font-semibold">{{ $duplicateResult['total_import'] }}</p>
            </div>
        </div>

        @if($duplicateResult['duplicate_count'] > 0)
        <div class="border-t border-yellow-200 pt-4">
            <h5 class="font-medium text-yellow-800 mb-3">Duplicate Resolution Required</h5>
            <p class="text-sm text-yellow-700 mb-4">Choose how to handle each duplicate card:</p>
            
            <form 
                hx-post="{{ route('flashcards.resolve-duplicates', $unit->id) }}"
                hx-target="#advanced-import-content"
                class="space-y-4">
                @csrf
                <input type="hidden" name="duplicates" value="{{ json_encode($duplicateResult['duplicates']) }}">
                <input type="hidden" name="import_data" value="{{ $importData }}">
                <input type="hidden" name="filename" value="{{ $filename }}">

                <!-- Global Action -->
                <div class="bg-yellow-25 p-3 rounded border">
                    <label class="text-sm font-medium text-yellow-800">Apply to all duplicates:</label>
                    <select name="merge_actions[global]" class="mt-1 block w-full text-sm border-gray-300 rounded-md">
                        <option value="">Choose individually...</option>
                        <option value="skip">Skip all duplicates</option>
                        <option value="update">Update existing cards</option>
                        <option value="keep_both">Keep both versions</option>
                        <option value="replace">Replace existing cards</option>
                    </select>
                </div>

                <!-- Individual Duplicate Cards -->
                <div class="max-h-64 overflow-y-auto space-y-3">
                    @foreach($duplicateResult['duplicates'] as $index => $duplicate)
                    <div class="bg-white border border-yellow-300 rounded p-3">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <p class="font-medium text-sm text-gray-900">{{ Str::limit($duplicate['import_card']['question'], 100) }}</p>
                                <p class="text-xs text-gray-600 mt-1">{{ Str::limit($duplicate['import_card']['answer'], 80) }}</p>
                            </div>
                            <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                {{ round($duplicate['similarity_score'] * 100) }}% match
                            </span>
                        </div>
                        
                        <div class="mt-2">
                            <label class="text-xs font-medium text-gray-700">Action:</label>
                            <select name="merge_actions[{{ $duplicate['import_index'] }}]" class="ml-2 text-xs border-gray-300 rounded">
                                <option value="skip" {{ $duplicate['suggested_action'] === 'skip' ? 'selected' : '' }}>Skip</option>
                                <option value="update" {{ $duplicate['suggested_action'] === 'update' ? 'selected' : '' }}>Update existing</option>
                                <option value="keep_both" {{ $duplicate['suggested_action'] === 'keep_both' ? 'selected' : '' }}>Keep both</option>
                                <option value="replace" {{ $duplicate['suggested_action'] === 'replace' ? 'selected' : '' }}>Replace existing</option>
                            </select>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-yellow-200">
                    <button type="button" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        @click="$event.target.closest('.fixed').remove()">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700">
                        Resolve Duplicates
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
    @endif

    <!-- Sample Cards Preview -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-900">Sample Cards Preview</h4>
            <p class="text-sm text-gray-600">Showing first 5 cards from import</p>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach(array_slice($parseResult['cards'], 0, 5) as $index => $card)
            <div class="px-4 py-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="text-xs font-medium text-gray-500">#{{ $index + 1 }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                {{ $card['card_type'] === 'basic' ? 'bg-blue-100 text-blue-800' : 
                                   ($card['card_type'] === 'cloze' ? 'bg-purple-100 text-purple-800' :
                                    ($card['card_type'] === 'multiple_choice' ? 'bg-green-100 text-green-800' : 
                                     'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst(str_replace('_', ' ', $card['card_type'])) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 mb-1">{{ Str::limit($card['question'], 120) }}</p>
                        <p class="text-sm text-gray-600">{{ Str::limit($card['answer'], 100) }}</p>
                        
                        @if(!empty($card['tags']))
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach(array_slice($card['tags'], 0, 3) as $tag)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $tag }}
                            </span>
                            @endforeach
                            @if(count($card['tags']) > 3)
                            <span class="text-xs text-gray-500">+{{ count($card['tags']) - 3 }} more</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        @if(count($parseResult['cards']) > 5)
        <div class="px-4 py-3 bg-gray-50 text-center text-sm text-gray-600 rounded-b-lg">
            ... and {{ count($parseResult['cards']) - 5 }} more cards
        </div>
        @endif
    </div>

    <!-- Import Actions (if no duplicates or duplicates resolved) -->
    @if(!$duplicateResult || $duplicateResult['duplicate_count'] === 0)
    <form 
        hx-post="{{ route('flashcards.execute-advanced-import', $unit->id) }}"
        hx-target="#flashcard-list"
        class="bg-green-50 border border-green-200 rounded-lg p-4">
        @csrf
        <input type="hidden" name="import_data" value="{{ $importData }}">
        <input type="hidden" name="filename" value="{{ $filename }}">
        <input type="hidden" name="import_type" value="{{ $typeInfo['import_type'] }}">
        <input type="hidden" name="import_options" value="{{ json_encode($options) }}">

        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-lg font-medium text-green-800">Ready to Import</h4>
                <p class="text-sm text-green-700">
                    {{ count($parseResult['cards']) }} cards will be imported to "{{ $unit->title }}"
                    @if(isset($parseResult['media_files']) && !empty($parseResult['media_files']))
                        with {{ count($parseResult['media_files']) }} media files
                    @endif
                </p>
            </div>
            <div class="flex space-x-3">
                <button type="button" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    @click="$event.target.closest('.fixed').remove()">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                    Import {{ count($parseResult['cards']) }} Cards
                </button>
            </div>
        </div>
    </form>
    @endif
</div>