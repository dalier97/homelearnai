{{-- Multiple Choice Flashcard Type --}}
@if($kidsMode)
    <!-- Kids Mode Multiple Choice -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🎯</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Pick the Right Answer!') }}</h4>
        </div>
        
        {{-- Question Display --}}
        <div class="prose max-w-none text-center mb-6">
            <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg">
                @if($flashcard->question_image_url)
                    <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mx-auto mb-4 max-w-full h-auto rounded-lg">
                @endif
                <div class="question-text">{{ $flashcard->question }}</div>
            </div>
        </div>
        
        {{-- Multiple Choice Options --}}
        <div class="choices-container space-y-4">
            @foreach($flashcard->choices as $index => $choice)
                <button onclick="selectChoice({{ $index }})" 
                        class="choice-btn w-full p-4 bg-white rounded-2xl shadow-lg border-3 border-purple-200 text-left hover:bg-purple-50 hover:border-purple-400 transition-all duration-300 transform hover:scale-105"
                        data-choice-index="{{ $index }}">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full border-2 border-purple-300 flex items-center justify-center mr-4 choice-indicator">
                            <span class="text-sm font-bold">{{ chr(65 + $index) }}</span>
                        </div>
                        <span class="text-lg font-medium">{{ $choice }}</span>
                    </div>
                </button>
            @endforeach
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
    <!-- Regular Mode Multiple Choice -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        <div class="prose max-w-none mb-6">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mb-4 max-w-full h-auto rounded">
            @endif
            <div class="question-text font-medium text-lg">{{ $flashcard->question }}</div>
        </div>
        
        {{-- Multiple Choice Options --}}
        <div class="choices-container space-y-3">
            @foreach($flashcard->choices as $index => $choice)
                <button onclick="selectChoice({{ $index }})" 
                        class="choice-btn w-full p-4 bg-white rounded border border-gray-300 text-left hover:bg-blue-50 hover:border-blue-300 transition-colors"
                        data-choice-index="{{ $index }}">
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-full border border-gray-400 flex items-center justify-center mr-3 choice-indicator">
                            <span class="text-sm">{{ chr(65 + $index) }}</span>
                        </div>
                        <span>{{ $choice }}</span>
                    </div>
                </button>
            @endforeach
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
let selectedChoiceIndex = null;
const correctChoices = @json($flashcard->correct_choices);

function selectChoice(choiceIndex) {
    selectedChoiceIndex = choiceIndex;
    
    // Clear previous selections
    document.querySelectorAll('.choice-btn').forEach(btn => {
        btn.classList.remove('selected', 'correct', 'incorrect');
    });
    
    // Mark selected choice
    const selectedBtn = document.querySelector(`[data-choice-index="${choiceIndex}"]`);
    selectedBtn.classList.add('selected');
    
    // Show feedback
    showMultipleChoiceFeedback(choiceIndex);
}

function showMultipleChoiceFeedback(selectedIndex) {
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
        
        // Highlight correct choices
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
    background-color: #dbeafe;
    border-color: #3b82f6;
}

.choice-btn.correct {
    background-color: #dcfce7;
    border-color: #16a34a;
}

.choice-btn.incorrect {
    background-color: #fee2e2;
    border-color: #dc2626;
}
</style>