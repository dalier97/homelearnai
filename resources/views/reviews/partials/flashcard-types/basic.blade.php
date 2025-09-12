{{-- Basic Flashcard Type (Question & Answer) --}}
@if($kidsMode)
    <!-- Kids Mode Basic Card -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🎯</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Memory Card!') }}</h4>
        </div>
        
        {{-- Question Display --}}
        <div class="prose max-w-none text-center">
            <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg mb-4">
                @if($flashcard->question_image_url)
                    <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mx-auto mb-4 max-w-full h-auto rounded-lg">
                @endif
                <div class="question-text">{{ $flashcard->question }}</div>
            </div>
        </div>
        
        {{-- Answer Display (Hidden Initially) --}}
        <div class="answer-content" style="display: none;">
            <div class="prose max-w-none text-center">
                <div class="text-lg font-semibold text-gray-800 bg-green-50 rounded-2xl p-6 shadow-lg border-4 border-green-200">
                    @if($flashcard->answer_image_url)
                        <img src="{{ $flashcard->answer_image_url }}" alt="Answer image" class="mx-auto mb-4 max-w-full h-auto rounded-lg">
                    @endif
                    <div class="answer-text">{{ $flashcard->answer }}</div>
                    @if($flashcard->hint)
                        <div class="mt-4 p-3 bg-yellow-100 rounded-xl border-2 border-yellow-300">
                            <div class="text-sm text-yellow-800">
                                <strong>💡 {{ __('Hint') }}:</strong> {{ $flashcard->hint }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Regular Mode Basic Card -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        <div class="prose max-w-none mb-4">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mb-4 max-w-full h-auto rounded">
            @endif
            <div class="question-text font-medium text-lg">{{ $flashcard->question }}</div>
        </div>
        
        {{-- Answer Display (Hidden Initially) --}}
        <div class="answer-content border-t pt-4 mt-4" style="display: none;">
            <div class="prose max-w-none">
                @if($flashcard->answer_image_url)
                    <img src="{{ $flashcard->answer_image_url }}" alt="Answer image" class="mb-4 max-w-full h-auto rounded">
                @endif
                <div class="answer-text p-4 bg-green-50 rounded border-l-4 border-green-400">{{ $flashcard->answer }}</div>
                @if($flashcard->hint)
                    <div class="mt-3 p-3 bg-yellow-50 rounded border-l-4 border-yellow-400">
                        <div class="text-sm text-gray-700">
                            <strong>{{ __('Hint') }}:</strong> {{ $flashcard->hint }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif