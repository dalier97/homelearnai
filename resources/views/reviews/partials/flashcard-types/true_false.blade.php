{{-- True/False Flashcard Type --}}
@if($kidsMode)
    <!-- Kids Mode True/False -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🤔</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('True or False?') }}</h4>
        </div>
        
        {{-- Question Display --}}
        <div class="prose max-w-none text-center mb-8">
            <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg">
                @if($flashcard->question_image_url)
                    <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mx-auto mb-4 max-w-full h-auto rounded-lg">
                @endif
                <div class="question-text">{{ $flashcard->question }}</div>
            </div>
        </div>
        
        {{-- True/False Options --}}
        <div class="choices-container flex justify-center space-x-6 mb-6">
            <button onclick="selectTrueFalse(0)" 
                    class="choice-btn true-btn flex flex-col items-center p-6 bg-white rounded-3xl shadow-lg border-3 border-green-200 hover:bg-green-50 hover:border-green-400 transition-all duration-300 transform hover:scale-110"
                    data-choice-index="0">
                <div class="text-4xl mb-2">✅</div>
                <span class="text-xl font-bold text-green-700">{{ __('TRUE') }}</span>
            </button>
            
            <button onclick="selectTrueFalse(1)" 
                    class="choice-btn false-btn flex flex-col items-center p-6 bg-white rounded-3xl shadow-lg border-3 border-red-200 hover:bg-red-50 hover:border-red-400 transition-all duration-300 transform hover:scale-110"
                    data-choice-index="1">
                <div class="text-4xl mb-2">❌</div>
                <span class="text-xl font-bold text-red-700">{{ __('FALSE') }}</span>
            </button>
        </div>
        
        {{-- Feedback Display (Hidden Initially) --}}
        <div class="feedback-content mt-6" style="display: none;">
            <div class="text-center">
                <div class="feedback-message p-6 rounded-2xl shadow-lg"></div>
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
@else
    <!-- Regular Mode True/False -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        <div class="prose max-w-none mb-6">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mb-4 max-w-full h-auto rounded">
            @endif
            <div class="question-text font-medium text-lg">{{ $flashcard->question }}</div>
        </div>
        
        {{-- True/False Options --}}
        <div class="choices-container flex justify-center space-x-4 mb-6">
            <button onclick="selectTrueFalse(0)" 
                    class="choice-btn true-btn flex items-center px-6 py-3 bg-white rounded border border-green-300 text-green-700 hover:bg-green-50 transition-colors"
                    data-choice-index="0">
                <span class="text-xl mr-2">✓</span>
                <span class="font-semibold">{{ __('TRUE') }}</span>
            </button>
            
            <button onclick="selectTrueFalse(1)" 
                    class="choice-btn false-btn flex items-center px-6 py-3 bg-white rounded border border-red-300 text-red-700 hover:bg-red-50 transition-colors"
                    data-choice-index="1">
                <span class="text-xl mr-2">✗</span>
                <span class="font-semibold">{{ __('FALSE') }}</span>
            </button>
        </div>
        
        {{-- Feedback Display (Hidden Initially) --}}
        <div class="feedback-content mt-4 border-t pt-4" style="display: none;">
            <div class="feedback-message p-4 rounded"></div>
            @if($flashcard->hint)
                <div class="mt-3 p-3 bg-yellow-50 rounded border-l-4 border-yellow-400">
                    <div class="text-sm text-gray-700">
                        <strong>{{ __('Hint') }}:</strong> {{ $flashcard->hint }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

<script>
let selectedTrueFalseIndex = null;
const correctChoices = @json($flashcard->correct_choices);

function selectTrueFalse(choiceIndex) {
    selectedTrueFalseIndex = choiceIndex;
    
    // Clear previous selections
    document.querySelectorAll('.choice-btn').forEach(btn => {
        btn.classList.remove('selected', 'correct', 'incorrect');
    });
    
    // Mark selected choice
    const selectedBtn = document.querySelector(`[data-choice-index="${choiceIndex}"]`);
    selectedBtn.classList.add('selected');
    
    // Show feedback
    showTrueFalseFeedback(choiceIndex);
}

function showTrueFalseFeedback(selectedIndex) {
    const feedbackContainer = document.querySelector('.feedback-content');
    const feedbackMessage = document.querySelector('.feedback-message');
    
    const isCorrect = correctChoices.includes(selectedIndex);
    
    if (isCorrect) {
        feedbackMessage.innerHTML = `
            <div class="text-green-800 bg-green-100 border-green-400 border rounded p-4">
                <div class="text-2xl mb-2">🎉</div>
                <div class="font-semibold">${'{{ __("Correct!") }}'}</div>
                <div class="text-sm mt-2">${'{{ $flashcard->answer }}'}</div>
            </div>
        `;
        document.querySelector(`[data-choice-index="${selectedIndex}"]`).classList.add('correct');
    } else {
        feedbackMessage.innerHTML = `
            <div class="text-red-800 bg-red-100 border-red-400 border rounded p-4">
                <div class="text-2xl mb-2">❌</div>
                <div class="font-semibold">${'{{ __("Not quite right") }}'}</div>
                <div class="text-sm mt-2">${'{{ __("The correct answer is") }}'}: {{ $flashcard->answer }}</div>
            </div>
        `;
        document.querySelector(`[data-choice-index="${selectedIndex}"]`).classList.add('incorrect');
        
        // Highlight correct choice
        correctChoices.forEach(correctIndex => {
            document.querySelector(`[data-choice-index="${correctIndex}"]`).classList.add('correct');
        });
    }
    
    feedbackContainer.style.display = 'block';
    
    // Enable rating buttons after selection
    setTimeout(() => {
        // This will be handled by the main review interface
        window.flashcardAnswered = { selectedIndex, isCorrect };
    }, 1000);
}
</script>

<style>
.choice-btn.selected {
    transform: scale(1.05);
}

.choice-btn.correct {
    background-color: #dcfce7;
    border-color: #16a34a;
}

.choice-btn.incorrect {
    background-color: #fee2e2;
    border-color: #dc2626;
}

.true-btn.correct {
    background-color: #dcfce7;
}

.false-btn.correct {
    background-color: #dcfce7;
}
</style>