<!-- Print Preview Display -->
<div class="print-preview-container">
    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center justify-between text-sm">
            <div>
                <strong>Layout:</strong> {{ ucfirst(str_replace('_', ' ', $layout)) }} | 
                <strong>Cards:</strong> {{ $flashcards->count() }} | 
                <strong>Page Size:</strong> {{ $options['page_size'] }} | 
                <strong>Font:</strong> {{ ucfirst($options['font_size']) }}
            </div>
            <div class="text-gray-600">
                Preview shows first 6 cards
            </div>
        </div>
    </div>
    
    <!-- Preview Content -->
    <div class="print-preview-content border border-gray-300 rounded-lg bg-white" 
         style="max-height: 500px; overflow-y: auto;">
        <div class="print-preview-pages">
            {!! $previewContent !!}
        </div>
    </div>
    
    <!-- Print-specific styles for preview -->
    <style>
        .print-preview-content {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        
        .print-preview-pages {
            padding: 20px;
            transform: scale(0.6);
            transform-origin: top left;
            width: 166.67%; /* Compensate for 0.6 scale */
        }
        
        /* Override any existing styles for preview */
        .print-preview-pages * {
            box-sizing: border-box;
        }
        
        /* Layout-specific preview adjustments */
        .print-preview-pages .index-card {
            margin-bottom: 20px;
        }
        
        .print-preview-pages .grid-container {
            margin-bottom: 40px;
        }
        
        .print-preview-pages .foldable-card {
            margin-bottom: 30px;
        }
        
        .print-preview-pages .study-item {
            margin-bottom: 30px;
        }
        
        /* Make sure cut lines and fold lines are visible in preview */
        .cut-line, .fold-line {
            opacity: 0.7;
        }
    </style>
    
    <!-- Cards Information -->
    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
        <h4 class="font-medium text-gray-900 mb-2">Card Types Summary</h4>
        <div class="text-sm text-gray-600">
            @php
                $cardTypeCounts = $flashcards->countBy('card_type');
            @endphp
            @foreach($cardTypeCounts as $type => $count)
                <span class="inline-block mr-4 mb-1">
                    <span class="inline-block w-3 h-3 rounded mr-1" 
                          style="background-color: {{ 
                              match($type) {
                                  'basic' => '#3b82f6',
                                  'multiple_choice' => '#10b981', 
                                  'true_false' => '#f59e0b',
                                  'cloze' => '#8b5cf6',
                                  'typed_answer' => '#6366f1',
                                  'image_occlusion' => '#ec4899',
                                  default => '#6b7280'
                              }
                          }}"></span>
                    {{ ucfirst(str_replace('_', ' ', $type)) }}: {{ $count }}
                </span>
            @endforeach
        </div>
    </div>
    
    <!-- Estimated Pages Info -->
    @php
        $estimatedPages = match($layout) {
            'index' => ceil($flashcards->count() / 4),
            'grid' => ceil($flashcards->count() / 6),
            'foldable' => ceil($flashcards->count() / 2),
            'study_sheet' => ceil($flashcards->count() / 3),
            default => ceil($flashcards->count() / 4)
        };
    @endphp
    
    <div class="mt-2 text-xs text-gray-500">
        <strong>Estimated pages:</strong> {{ $estimatedPages }} 
        @if($estimatedPages > 10)
            <span class="text-orange-600">(Large print job - consider splitting into smaller batches)</span>
        @endif
    </div>
</div>