{{-- Session Completion Success Message --}}
<div class="text-center py-8">
    <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    
    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('session_completed_exclamation') }}</h3>
    <p class="text-gray-600 mb-4">{{ __('evidence_captured_for_child', ['name' => $child->name]) }}</p>
    
    <div class="space-y-2 text-sm text-gray-600">
        @if($session->evidence_notes)
            <div class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('notes_captured') }}
            </div>
        @endif
        
        @if($session->evidence_photos && count($session->evidence_photos) > 0)
            <div class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('photos_uploaded', ['count' => count($session->evidence_photos)]) }}
            </div>
        @endif
        
        @if($session->evidence_voice_memo)
            <div class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                {{ __('voice_memo_recorded') }}
            </div>
        @endif
        
        @if($session->evidence_attachments && count($session->evidence_attachments) > 0)
            <div class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                {{ __('attachments_uploaded', ['count' => count($session->evidence_attachments)]) }}
            </div>
        @endif
    </div>
    
    <div class="mt-6">
        <p class="text-sm text-gray-500">{{ __('review_card_created_tomorrow') }}</p>
    </div>
</div>