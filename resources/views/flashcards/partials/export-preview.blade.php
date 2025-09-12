<!-- Export Preview -->
<div class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center space-x-4">
        <button type="button" 
                class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center"
                onclick="showExportOptions()"
                data-testid="back-to-options">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Export Options
        </button>
    </div>

    <!-- Export Summary -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-green-800 mb-2">Export Preview</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-green-600">Format:</span>
                <span class="font-medium">{{ $formatName }}</span>
            </div>
            <div>
                <span class="text-green-600">Cards:</span>
                <span class="font-medium">{{ $totalCards }}</span>
            </div>
            <div>
                <span class="text-green-600">Unit:</span>
                <span class="font-medium">{{ $unit->name }}</span>
            </div>
            <div>
                <span class="text-green-600">Ready:</span>
                <span class="font-medium text-green-700">
                    @if($canExport)
                        Yes ✓
                    @else
                        No ✗
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Format Description -->
    @if(isset($previewData['description']))
        <div class="bg-blue-50 rounded-lg p-4">
            <h5 class="text-sm font-medium text-blue-800 mb-2">Format Information</h5>
            <p class="text-sm text-blue-700">{{ $previewData['description'] }}</p>
        </div>
    @endif

    <!-- Preview Content -->
    @if(isset($previewData['samples']) && !empty($previewData['samples']))
        <div class="border border-gray-200 rounded-lg">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h5 class="text-sm font-medium text-gray-900">
                    Preview ({{ $previewData['sample_count'] ?? 0 }} of {{ $previewData['total_count'] ?? 0 }} cards)
                </h5>
            </div>
            
            <div class="p-4 space-y-4 max-h-64 overflow-y-auto">
                @foreach($previewData['samples'] as $sample)
                    @switch($format)
                        @case('anki')
                        @case('supermemo')
                        @case('mnemosyne')
                            @if(isset($sample['question']) && isset($sample['answer']))
                                <div class="border border-gray-100 rounded p-3 text-sm">
                                    <div class="mb-2">
                                        <strong class="text-gray-600">Q:</strong> 
                                        <span class="text-gray-800">{{ $sample['question'] }}</span>
                                    </div>
                                    <div>
                                        <strong class="text-gray-600">A:</strong> 
                                        <span class="text-gray-800">{{ $sample['answer'] }}</span>
                                    </div>
                                    @if(isset($sample['type']))
                                        <div class="mt-2 text-xs text-blue-600">
                                            Type: {{ $sample['type'] }}
                                        </div>
                                    @endif
                                </div>
                            @elseif(isset($sample['example']))
                                <div class="border border-gray-100 rounded p-3 text-sm">
                                    <code class="text-gray-800">{{ $sample['example'] }}</code>
                                </div>
                            @elseif(isset($sample['xml_format']))
                                <div class="border border-gray-100 rounded p-3 text-sm">
                                    <code class="text-gray-800 text-xs">{{ $sample['xml_format'] }}</code>
                                </div>
                            @endif
                            @break

                        @case('quizlet')
                            @if(isset($sample['example']))
                                <div class="border border-gray-100 rounded p-3 text-sm">
                                    <code class="text-gray-800 whitespace-pre">{{ $sample['example'] }}</code>
                                </div>
                            @endif
                            @break

                        @case('csv')
                            @if(is_string($sample))
                                <div class="text-sm text-gray-600">
                                    {{ $sample }}
                                </div>
                            @endif
                            @break

                        @case('json')
                            @if(isset($sample['structure']))
                                <div class="border border-gray-100 rounded p-3 text-sm">
                                    <div><strong>Structure:</strong> {{ $sample['structure'] }}</div>
                                    @if(isset($sample['features']))
                                        <div class="mt-1"><strong>Features:</strong> {{ $sample['features'] }}</div>
                                    @endif
                                </div>
                            @endif
                            @break
                    @endswitch
                @endforeach
            </div>
        </div>
    @endif

    <!-- Format-Specific Options Summary -->
    @if(!empty($options))
        <div class="border border-gray-200 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-900 mb-3">Export Options</h5>
            <div class="space-y-2 text-sm">
                @foreach($options as $key => $value)
                    <div class="flex justify-between">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                        <span class="text-gray-800">
                            @if(is_bool($value))
                                {{ $value ? 'Yes' : 'No' }}
                            @else
                                {{ $value }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
        <button type="button" 
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 
                       hover:bg-gray-50 transition-colors"
                onclick="showExportOptions()">
            Back to Options
        </button>
        
        @if($canExport)
            <button type="button" 
                    class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium 
                           hover:bg-green-700 transition-colors flex items-center"
                    onclick="downloadExport()"
                    data-testid="download-export">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 10v6m0 0l-4-4m4 4l4-4m-4-4V3"/>
                </svg>
                Download Export
            </button>
        @else
            <button type="button" 
                    class="px-4 py-2 bg-gray-400 text-white rounded-md text-sm font-medium cursor-not-allowed"
                    disabled>
                Cannot Export
            </button>
        @endif
    </div>
</div>

<!-- Store export parameters -->
<script>
window.exportParams = {
    unitId: {{ $unit->id }},
    format: '{{ $format }}',
    totalCards: {{ $totalCards }},
    options: @json($options),
    flashcardIds: @json($flashcards->pluck('id')->toArray())
};

function showExportOptions() {
    htmx.ajax('GET', '{{ route('flashcards.export.options', $unit->id) }}', {
        target: '#export-content',
        swap: 'innerHTML'
    });
}

function downloadExport() {
    // Show loading state
    const downloadBtn = document.querySelector('[data-testid="download-export"]');
    const originalText = downloadBtn.innerHTML;
    downloadBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Exporting...
    `;
    downloadBtn.disabled = true;
    
    // Create download form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('flashcards.export.download', $unit->id) }}';
    form.style.display = 'none';
    
    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Add export format
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'export_format';
    formatInput.value = window.exportParams.format;
    form.appendChild(formatInput);
    
    // Add selected cards
    window.exportParams.flashcardIds.forEach(id => {
        const cardInput = document.createElement('input');
        cardInput.type = 'hidden';
        cardInput.name = 'selected_cards[]';
        cardInput.value = id;
        form.appendChild(cardInput);
    });
    
    // Add format-specific options
    Object.entries(window.exportParams.options).forEach(([key, value]) => {
        const optionInput = document.createElement('input');
        optionInput.type = 'hidden';
        optionInput.name = key;
        optionInput.value = value;
        form.appendChild(optionInput);
    });
    
    // Submit form
    document.body.appendChild(form);
    form.submit();
    
    // Restore button state after short delay
    setTimeout(() => {
        downloadBtn.innerHTML = originalText;
        downloadBtn.disabled = false;
        document.body.removeChild(form);
    }, 2000);
}
</script>