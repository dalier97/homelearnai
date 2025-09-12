{{-- Typed Answer Flashcard Type --}}
@if($kidsMode)
    <!-- Kids Mode Typed Answer -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">⌨️</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Type Your Answer!') }}</h4>
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
        
        {{-- Answer Input --}}
        <div class="text-center mb-6">
            <input type="text" 
                   id="typed-answer-input" 
                   class="w-full max-w-md mx-auto px-6 py-4 text-xl text-center rounded-2xl border-3 border-purple-300 focus:border-purple-500 focus:outline-none shadow-lg"
                   placeholder="{{ __('Type your answer here...') }}"
                   onkeypress="if(event.key==='Enter') checkTypedAnswer()">
        </div>
        
        {{-- Submit Button --}}
        <div class="text-center mb-6">
            <button onclick="checkTypedAnswer()" 
                    class="submit-btn bg-gradient-to-r from-purple-400 to-pink-500 text-white px-8 py-4 rounded-full text-xl font-bold shadow-xl hover:shadow-2xl transform hover:scale-110 transition-all duration-300">
                <span class="text-2xl mr-2">✨</span>
                {{ __('Check Answer!') }}
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
    <!-- Regular Mode Typed Answer -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        <div class="prose max-w-none mb-6">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" alt="Question image" class="mb-4 max-w-full h-auto rounded">
            @endif
            <div class="question-text font-medium text-lg">{{ $flashcard->question }}</div>
        </div>
        
        {{-- Answer Input --}}
        <div class="mb-4">
            <input type="text" 
                   id="typed-answer-input" 
                   class="w-full px-4 py-3 text-lg border border-gray-300 rounded focus:border-blue-500 focus:outline-none"
                   placeholder="{{ __('Type your answer here...') }}"
                   onkeypress="if(event.key==='Enter') checkTypedAnswer()">
        </div>
        
        {{-- Submit Button --}}
        <div class="text-center mb-4">
            <button onclick="checkTypedAnswer()" 
                    class="submit-btn bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors">
                {{ __('Check Answer') }}
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
const correctAnswer = `{!! addslashes($flashcard->answer) !!}`;
let userTypedAnswer = '';

// Focus input on load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('typed-answer-input').focus();
});

function checkTypedAnswer() {
    const input = document.getElementById('typed-answer-input');
    userTypedAnswer = input.value.trim();
    
    if (!userTypedAnswer) {
        input.focus();
        return;
    }
    
    // Disable input and button
    input.disabled = true;
    document.querySelector('.submit-btn').style.display = 'none';
    
    // Check answer (case-insensitive)
    const isCorrect = userTypedAnswer.toLowerCase() === correctAnswer.toLowerCase();
    
    // Visual feedback on input
    input.classList.remove('correct', 'incorrect');
    if (isCorrect) {
        input.classList.add('correct');
    } else {
        input.classList.add('incorrect');
        // Show correct answer after delay
        setTimeout(() => {
            input.value = correctAnswer;
            input.classList.add('correct');
            input.classList.remove('incorrect');
        }, 2000);
    }
    
    showTypedAnswerFeedback(isCorrect);
}

function showTypedAnswerFeedback(isCorrect) {
    const feedbackContainer = document.querySelector('.feedback-content');
    const feedbackMessage = document.querySelector('.feedback-message');
    
    if (isCorrect) {
        feedbackMessage.innerHTML = `
            <div class="text-green-800 bg-green-100 border-green-400 border rounded p-4">
                <div class="text-2xl mb-2">🎉</div>
                <div class="font-semibold">${'{{ __("Correct!") }}'}</div>
                <div class="text-sm mt-2">${'{{ __("Perfect match!") }}'}</div>
            </div>
        `;
    } else {
        feedbackMessage.innerHTML = `
            <div class="text-orange-800 bg-orange-100 border-orange-400 border rounded p-4">
                <div class="text-2xl mb-2">📝</div>
                <div class="font-semibold">${'{{ __("Close, but not quite!") }}'}</div>
                <div class="text-sm mt-2">${'{{ __("The correct answer is") }}'}: <strong>${correctAnswer}</strong></div>
            </div>
        `;
    }
    
    feedbackContainer.style.display = 'block';
    
    // Enable rating buttons after checking answer
    setTimeout(() => {
        window.flashcardAnswered = { userAnswer: userTypedAnswer, isCorrect, correctAnswer };
    }, 1000);
}
</script>

<style>
#typed-answer-input.correct {
    border-color: #16a34a;
    background-color: #dcfce7;
    color: #16a34a;
}

#typed-answer-input.incorrect {
    border-color: #dc2626;
    background-color: #fee2e2;
    color: #dc2626;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 50%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
</style>