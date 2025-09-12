{{-- Individual Review Card for Session or Flashcard --}}
@php
    $topic = $review->topic;
    $session = $review->session;
    $flashcard = $review->flashcard;
    $kidsMode = session('kids_mode_active', false);
    $isFlashcardReview = $review->isFlashcardReview();
@endphp

<div class="space-y-6" data-review-id="{{ $review->id }}">
    {{-- Topic Information --}}
    @if($kidsMode)
        <!-- Kids Mode Header - Fun and Engaging -->
        <div class="text-center bg-gradient-to-r from-purple-400 to-pink-500 rounded-3xl p-8 text-white">
            <div class="text-6xl mb-4 animate-bounce">🌟</div>
            @if($isFlashcardReview && $flashcard)
                <h3 class="text-3xl font-bold mb-3 drop-shadow-lg">{{ __('Flashcard Challenge!') }}</h3>
                <p class="text-lg opacity-90">{{ $flashcard->unit->name ?? __('Study Cards') }}</p>
            @else
                <h3 class="text-3xl font-bold mb-3 drop-shadow-lg">{{ $topic?->title ?? __('Mystery Challenge!') }}</h3>
            @endif
            <div class="flex justify-center space-x-2 mb-4">
                @for($i = 1; $i <= 5; $i++)
                    <div class="w-6 h-6 rounded-full @if($i <= $review->repetitions + 1) bg-yellow-300 @else bg-white/30 @endif flex items-center justify-center">
                        @if($i <= $review->repetitions + 1)
                            <span class="text-xs">⭐</span>
                        @endif
                    </div>
                @endfor
            </div>
            <p class="text-lg font-semibold">{{ __('Star Level: :level', ['level' => min($review->repetitions + 1, 5)]) }}</p>
            @if($review->isOverdue())
                <div class="mt-3 bg-red-400 rounded-2xl px-4 py-2 inline-block">
                    <span class="text-lg font-bold">🔥 {{ __('Hot Challenge!') }} 🔥</span>
                </div>
            @endif
        </div>
    @else
        <!-- Regular Mode Header -->
        <div class="text-center">
            @if($isFlashcardReview && $flashcard)
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('Flashcard Review') }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ $flashcard->unit->name ?? __('Study Cards') }} - {{ ucfirst($flashcard->card_type) }}</p>
            @else
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $topic?->title ?? __('unknown_topic') }}</h3>
            @endif
            <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $review->getStatusColor() }}">
                    {{ ucfirst($review->status) }}
                </span>
                <span>{{ __('repetitions_count', ['count' => $review->repetitions]) }}</span>
                <span>{{ __('interval_formatted', ['interval' => $review->getFormattedInterval()]) }}</span>
                @if($review->isOverdue())
                    <span class="text-red-600 font-medium">{{ __('days_overdue', ['days' => abs($review->getDaysUntilDue())]) }}</span>
                @endif
            </div>
        </div>
    @endif

    {{-- Content/Question Section --}}
    @if($isFlashcardReview && $flashcard)
        @include('reviews.partials.flashcard-content', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
    @else
        {{-- Topic Content/Question --}}
        @if($kidsMode)
            <!-- Kids Mode Content - Fun and Visual -->
            <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-3">🧠</div>
                    <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Brain Challenge Time!') }}</h4>
                </div>
                <div class="prose max-w-none text-center">
                    @if($topic?->content)
                        <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg">
                            {!! nl2br(e($topic->content)) !!}
                        </div>
                    @else
                        <div class="text-lg font-semibold text-purple-700 bg-white rounded-2xl p-6 shadow-lg">
                            <p>{{ __('What do you remember about this topic?') }}</p>
                            <div class="text-4xl mt-3">🤔</div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Regular Mode Content -->
            <div class="review-question bg-gray-50 rounded-lg p-6">
                <div class="prose max-w-none">
                    @if($topic?->content)
                        {!! nl2br(e($topic->content)) !!}
                    @else
                        <p class="text-gray-600 italic">{{ __('review_this_topic_and_assess_your_understanding') }}</p>
                    @endif
                </div>
            </div>
        @endif
    @endif
        
        @if($session?->notes)
            <div class="mt-4 p-3 bg-blue-50 rounded border-l-4 border-blue-200">
                <h4 class="text-sm font-medium text-blue-900 mb-1">{{ __('session_notes') }}:</h4>
                <p class="text-sm text-blue-800">{{ $session->notes }}</p>
            </div>
        @endif

        {{-- Evidence from original session --}}
        @if($session?->hasEvidence())
            <div class="mt-4 p-3 bg-green-50 rounded border-l-4 border-green-200">
                <h4 class="text-sm font-medium text-green-900 mb-2">{{ __('learning_evidence') }}:</h4>
                
                @if($session->evidence_notes)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('notes') }}:</span>
                        <p class="text-sm text-green-800">{{ $session->evidence_notes }}</p>
                    </div>
                @endif
                
                @if($session->evidence_photos && count($session->evidence_photos) > 0)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('photos') }}:</span>
                        <div class="flex space-x-2 mt-1">
                            @foreach($session->evidence_photos as $photoUrl)
                                <img src="{{ asset($photoUrl) }}" alt="Evidence photo" class="w-16 h-16 object-cover rounded border">
                            @endforeach
                        </div>
                    </div>
                @endif
                
                @if($session->evidence_voice_memo)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('voice_memo') }}:</span>
                        <audio controls class="w-full h-8 mt-1">
                            <source src="{{ asset($session->evidence_voice_memo) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                @endif
                
                @if($session->evidence_attachments && count($session->evidence_attachments) > 0)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">Files:</span>
                        <div class="mt-1">
                            @foreach($session->evidence_attachments as $attachmentUrl)
                                <a href="{{ asset($attachmentUrl) }}" target="_blank" class="text-xs text-green-700 hover:text-green-900 underline block">
                                    📎 {{ basename($attachmentUrl) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Instructions --}}
    @if(!$isFlashcardReview)
        @if($kidsMode)
            <!-- Kids Mode Instructions - Fun and Simple -->
            <div class="text-center">
                <div class="bg-gradient-to-r from-yellow-200 to-orange-200 rounded-3xl p-6 mb-6">
                    <div class="text-5xl mb-3">💭</div>
                    <p class="text-xl font-bold text-purple-800 mb-4">{{ __('Think about what you remember!') }}</p>
                    <button onclick="showAnswer(this)" 
                            class="show-answer-btn bg-gradient-to-r from-green-400 to-blue-500 text-white px-8 py-4 rounded-full text-xl font-bold shadow-xl hover:shadow-2xl transform hover:scale-110 transition-all duration-300">
                        <span class="text-2xl mr-2">👀</span>
                        {{ __('Show Answer!') }}
                    </button>
                </div>
                <div class="answer-section" style="display: none;">
                    <div class="bg-gradient-to-r from-pink-200 to-purple-200 rounded-3xl p-6 mb-6">
                        <div class="text-4xl mb-3">🌟</div>
                        <p class="text-xl font-bold text-purple-800 mb-4">{{ __('How did you do?') }}</p>
                        <p class="text-lg text-purple-700">{{ __('Pick the star that shows how well you remembered!') }}</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Regular Mode Instructions and Buttons -->
            <div class="text-center text-sm text-gray-600">
                <p class="mb-4">{{ __('think_about_how_well_you_remember_this_topic_then_reveal_the_answer') }}</p>
                <button onclick="showAnswer(this)" 
                        class="show-answer-btn mb-4 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    {{ __('show_answer') }}
                </button>
                <div class="answer-section" style="display: none;">
                    <p class="mb-2 text-green-700 font-medium">{{ __('now_rate_your_recall') }}</p>
                    <div class="flex justify-center space-x-4 text-xs">
                        <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">1</kbd> {{ __('again') }}</span>
                        <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">2</kbd> {{ __('hard') }}</span>
                        <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">3</kbd> {{ __('good') }}</span>
                        <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">4</kbd> {{ __('easy') }}</span>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- Flashcard Review Instructions --}}
        @if($kidsMode)
            <!-- Kids Mode Flashcard Instructions -->
            <div class="text-center">
                <div class="bg-gradient-to-r from-pink-200 to-purple-200 rounded-3xl p-6 mb-6" id="flashcard-instructions">
                    <div class="text-4xl mb-3">🌟</div>
                    <p class="text-xl font-bold text-purple-800 mb-4">{{ __('How did you do?') }}</p>
                    <p class="text-lg text-purple-700">{{ __('Pick the star that shows how well you remembered!') }}</p>
                </div>
            </div>
        @else
            <!-- Regular Mode Flashcard Instructions -->
            <div class="text-center text-sm text-gray-600" id="flashcard-instructions">
                <p class="mb-2 text-green-700 font-medium">{{ __('Rate your performance on this flashcard') }}</p>
                <div class="flex justify-center space-x-4 text-xs">
                    <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">1</kbd> {{ __('again') }}</span>
                    <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">2</kbd> {{ __('hard') }}</span>
                    <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">3</kbd> {{ __('good') }}</span>
                    <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">4</kbd> {{ __('easy') }}</span>
                </div>
            </div>
        @endif
    @endif

    {{-- Action Buttons Section --}}
    @if($kidsMode)
        {{-- Kids Mode Action Buttons - Large and Colorful --}}
        <div class="grid grid-cols-2 gap-6">
            <button onclick="processReviewResult({{ $review->id }}, 'again')" 
                    class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-red-400 to-red-600 rounded-3xl text-white hover:from-red-500 hover:to-red-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                <div class="text-4xl mb-3">😅</div>
                <span class="text-xl font-bold">{{ __('Oops!') }}</span>
                <span class="text-lg">{{ __('Try again soon') }}</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'hard')" 
                    class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-orange-400 to-orange-600 rounded-3xl text-white hover:from-orange-500 hover:to-orange-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                <div class="text-4xl mb-3">🤔</div>
                <span class="text-xl font-bold">{{ __('Tricky!') }}</span>
                <span class="text-lg">{{ __('Show me sooner') }}</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'good')" 
                    class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-green-400 to-green-600 rounded-3xl text-white hover:from-green-500 hover:to-green-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                <div class="text-4xl mb-3">😊</div>
                <span class="text-xl font-bold">{{ __('Good Job!') }}</span>
                <span class="text-lg">{{ __('Perfect timing') }}</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'easy')" 
                    class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-blue-400 to-blue-600 rounded-3xl text-white hover:from-blue-500 hover:to-blue-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                <div class="text-4xl mb-3">🚀</div>
                <span class="text-xl font-bold">{{ __('Super Easy!') }}</span>
                <span class="text-lg">{{ __('I\'m a star!') }}</span>
            </button>
        </div>
    @else
        {{-- Regular Mode Action Buttons --}}
        <div class="grid grid-cols-4 gap-3">
            <button onclick="processReviewResult({{ $review->id }}, 'again')" 
                    class="flex flex-col items-center justify-center p-4 border-2 border-red-200 rounded-lg text-red-700 hover:bg-red-50 hover:border-red-300 transition-colors">
                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-sm font-medium">Again</span>
                <span class="text-xs text-gray-500">< 1 day</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'hard')" 
                    class="flex flex-col items-center justify-center p-4 border-2 border-orange-200 rounded-lg text-orange-700 hover:bg-orange-50 hover:border-orange-300 transition-colors">
                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-sm font-medium">Hard</span>
                <span class="text-xs text-gray-500">< usual</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'good')" 
                    class="flex flex-col items-center justify-center p-4 border-2 border-green-200 rounded-lg text-green-700 hover:bg-green-50 hover:border-green-300 transition-colors">
                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">Good</span>
                <span class="text-xs text-gray-500">Normal</span>
            </button>

            <button onclick="processReviewResult({{ $review->id }}, 'easy')" 
                    class="flex flex-col items-center justify-center p-4 border-2 border-blue-200 rounded-lg text-blue-700 hover:bg-blue-50 hover:border-blue-300 transition-colors">
                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-sm font-medium">Easy</span>
                <span class="text-xs text-gray-500">Longer</span>
            </button>
        </div>
    @endif

    {{-- SRS Algorithm Details (collapsible) - Hidden in Kids Mode --}}
    @if(!$kidsMode)
    <details class="text-xs text-gray-500">
        <summary class="cursor-pointer hover:text-gray-700">SRS Details</summary>
        <div class="mt-2 p-3 bg-gray-50 rounded">
            <div class="grid grid-cols-2 gap-2">
                <div>Current interval: {{ $review->interval_days }} days</div>
                <div>Ease factor: {{ number_format($review->ease_factor, 2) }}</div>
                <div>Due date: {{ $review->due_date?->translatedFormat('M j, Y') }}</div>
                <div>Last reviewed: {{ $review->last_reviewed_at?->translatedFormat('M j, Y g:i A') ?? 'Never' }}</div>
            </div>
        </div>
    </details>
    @endif
</div>

<script>
function showAnswer(button) {
    button.style.display = 'none';
    button.parentElement.querySelector('.answer-section').style.display = 'block';
}
</script>