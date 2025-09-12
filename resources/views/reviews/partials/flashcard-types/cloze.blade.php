{{-- Cloze Deletion Flashcard Type --}}
@if($kidsMode)
    <!-- Kids Mode Cloze -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🧩</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Fill in the Blanks!') }}</h4>
        </div>
        
        {{-- Cloze Text Display with Input Fields --}}
        <div class="prose max-w-none text-center mb-6">
            <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg">
                @if($flashcard->question_image_url)
                    <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mx-auto mb-4 max-w-full h-auto rounded-lg">
                @endif
                <div class="cloze-text" id="cloze-container">
                    {!! $flashcard->cloze_text !!}
                </div>
            </div>
        </div>
        
        {{-- Submit Button --}}
        <div class="text-center mb-6">
            <button onclick="checkClozeAnswers()" 
                    class="submit-btn bg-gradient-to-r from-purple-400 to-pink-500 text-white px-8 py-4 rounded-full text-xl font-bold shadow-xl hover:shadow-2xl transform hover:scale-110 transition-all duration-300">
                <span class="text-2xl mr-2">🎯</span>
                {{ __('Check My Answers!') }}
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
    <!-- Regular Mode Cloze -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        <div class="prose max-w-none mb-6">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mb-4 max-w-full h-auto rounded">
            @endif
            <div class="question-text font-medium text-lg mb-4">{{ $flashcard->question }}</div>
            
            {{-- Cloze Text with Blanks --}}
            <div class="cloze-text bg-white p-4 rounded border" id="cloze-container">
                {!! $flashcard->cloze_text !!}
            </div>
        </div>
        
        {{-- Submit Button --}}
        <div class="text-center mb-4">
            <button onclick="checkClozeAnswers()" 
                    class="submit-btn bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors">
                {{ __('Check Answers') }}
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
const correctAnswers = @json($flashcard->cloze_answers);
let userAnswers = [];

// Initialize cloze text with input fields
document.addEventListener('DOMContentLoaded', function() {
    initializeClozeText();
});

function initializeClozeText() {
    const clozeContainer = document.getElementById('cloze-container');
    let clozeText = `{!! addslashes($flashcard->cloze_text) !!}`;
    
    // Replace {{}} placeholders with input fields
    let blankIndex = 0;
    clozeText = clozeText.replace(/\{\{.*?\}\}/g, function() {
        return `<input type="text" class="cloze-input inline-input" data-blank-index="${blankIndex++}" placeholder="?" style="border-bottom: 2px solid #3b82f6; background: transparent; width: 100px; text-align: center; font-weight: bold; color: #1f2937;">`;
    });
    
    clozeContainer.innerHTML = clozeText;
    
    // Focus first input
    const firstInput = clozeContainer.querySelector('.cloze-input');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Add enter key handler for inputs
    clozeContainer.querySelectorAll('.cloze-input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                checkClozeAnswers();
            }
        });
    });
}

function checkClozeAnswers() {
    const inputs = document.querySelectorAll('.cloze-input');
    userAnswers = [];
    let allCorrect = true;
    
    inputs.forEach((input, index) => {
        const userAnswer = input.value.trim().toLowerCase();
        const correctAnswer = correctAnswers[index] ? correctAnswers[index].toLowerCase() : '';
        
        userAnswers.push(input.value.trim());
        
        // Clear previous styling
        input.classList.remove('correct', 'incorrect');
        
        if (userAnswer === correctAnswer) {
            input.classList.add('correct');
        } else {
            input.classList.add('incorrect');
            allCorrect = false;
            
            // Show correct answer
            setTimeout(() => {
                input.value = correctAnswers[index] || '';
                input.classList.add('correct');
                input.classList.remove('incorrect');
            }, 2000);
        }
        
        // Disable input
        input.disabled = true;
    });
    
    showClozeFeedback(allCorrect);
}

function showClozeFeedback(allCorrect) {
    const feedbackContainer = document.querySelector('.feedback-content');
    const feedbackMessage = document.querySelector('.feedback-message');
    const submitBtn = document.querySelector('.submit-btn');
    
    // Hide submit button
    submitBtn.style.display = 'none';
    
    if (allCorrect) {
        feedbackMessage.innerHTML = `
            <div class="text-green-800 bg-green-100 border-green-400 border rounded p-4">
                <div class="text-2xl mb-2">🎉</div>
                <div class="font-semibold">Perfect!</div>
                <div class="text-sm mt-2">All blanks filled correctly!</div>
            </div>
        `;
    } else {
        feedbackMessage.innerHTML = `
            <div class="text-orange-800 bg-orange-100 border-orange-400 border rounded p-4">
                <div class="text-2xl mb-2">📝</div>
                <div class="font-semibold">Good effort!</div>
                <div class="text-sm mt-2">Check the highlighted answers</div>
            </div>
        `;
    }
    
    feedbackContainer.style.display = 'block';
    
    // Enable rating buttons after checking answers
    setTimeout(() => {
        window.flashcardAnswered = { userAnswers, allCorrect, correctAnswers };
    }, 1000);
}
</script>

<style>
.cloze-input.correct {
    border-bottom-color: #16a34a;
    color: #16a34a;
    background-color: #dcfce7;
}

.cloze-input.incorrect {
    border-bottom-color: #dc2626;
    color: #dc2626;
    background-color: #fee2e2;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 50%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.inline-input {
    margin: 0 4px;
    min-width: 60px;
    max-width: 120px;
}
</style>