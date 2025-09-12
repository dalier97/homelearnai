{{-- Image Occlusion Flashcard Type --}}
@if($kidsMode)
    <!-- Kids Mode Image Occlusion -->
    <div class="review-question bg-gradient-to-r from-blue-100 to-green-100 rounded-3xl p-8 border-4 border-purple-300">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🖼️</div>
            <h4 class="text-2xl font-bold text-purple-800 mb-4">{{ __('Click to Reveal!') }}</h4>
        </div>
        
        {{-- Question Display --}}
        @if($flashcard->question)
            <div class="prose max-w-none text-center mb-6">
                <div class="text-lg font-semibold text-gray-800 bg-white rounded-2xl p-6 shadow-lg">
                    <div class="question-text">{{ $flashcard->question }}</div>
                </div>
            </div>
        @endif
        
        {{-- Image with Occlusion Overlays --}}
        <div class="image-container relative mx-auto bg-white rounded-2xl p-4 shadow-lg" style="max-width: 600px;">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" 
                     alt="Question image" 
                     class="w-full h-auto rounded-lg" 
                     id="occlusion-image">
                
                {{-- Occlusion Overlays --}}
                @if($flashcard->occlusion_data)
                    @foreach($flashcard->occlusion_data as $index => $occlusion)
                        <div class="occlusion-overlay" 
                             data-occlusion-index="{{ $index }}"
                             onclick="revealOcclusion({{ $index }})"
                             style="position: absolute; 
                                    top: {{ $occlusion['y'] ?? '10' }}%; 
                                    left: {{ $occlusion['x'] ?? '10' }}%; 
                                    width: {{ $occlusion['width'] ?? '100' }}px; 
                                    height: {{ $occlusion['height'] ?? '50' }}px; 
                                    background: linear-gradient(45deg, #ff6b6b, #4ecdc4); 
                                    border-radius: 10px; 
                                    cursor: pointer; 
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center;
                                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                                    transition: all 0.3s ease;
                                    transform: scale(1);">
                            <span class="text-white font-bold text-lg">?</span>
                        </div>
                    @endforeach
                @endif
            @else
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-2">🖼️</div>
                    <p>{{ __('No image available') }}</p>
                </div>
            @endif
        </div>
        
        {{-- Instructions --}}
        <div class="text-center mt-6 mb-4">
            <p class="text-lg text-purple-700 font-medium">{{ __('Click on the colored areas to reveal the answers!') }}</p>
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
    <!-- Regular Mode Image Occlusion -->
    <div class="review-question bg-gray-50 rounded-lg p-6">
        {{-- Question Display --}}
        @if($flashcard->question)
            <div class="prose max-w-none mb-6">
                <div class="question-text font-medium text-lg">{{ $flashcard->question }}</div>
            </div>
        @endif
        
        {{-- Image with Occlusion Overlays --}}
        <div class="image-container relative mx-auto bg-white rounded p-4 border" style="max-width: 600px;">
            @if($flashcard->question_image_url)
                <img src="{{ $flashcard->question_image_url }}" 
                     alt="Question image" 
                     class="w-full h-auto rounded" 
                     id="occlusion-image">
                
                {{-- Occlusion Overlays --}}
                @if($flashcard->occlusion_data)
                    @foreach($flashcard->occlusion_data as $index => $occlusion)
                        <div class="occlusion-overlay" 
                             data-occlusion-index="{{ $index }}"
                             onclick="revealOcclusion({{ $index }})"
                             style="position: absolute; 
                                    top: {{ $occlusion['y'] ?? '10' }}%; 
                                    left: {{ $occlusion['x'] ?? '10' }}%; 
                                    width: {{ $occlusion['width'] ?? '100' }}px; 
                                    height: {{ $occlusion['height'] ?? '50' }}px; 
                                    background: rgba(59, 130, 246, 0.8); 
                                    border-radius: 4px; 
                                    cursor: pointer; 
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center;
                                    border: 2px solid #3b82f6;
                                    transition: all 0.3s ease;">
                            <span class="text-white font-bold">?</span>
                        </div>
                    @endforeach
                @endif
            @else
                <div class="text-center text-gray-500 py-8">
                    <p>{{ __('No image available') }}</p>
                </div>
            @endif
        </div>
        
        {{-- Instructions --}}
        <div class="text-center mt-4 text-sm text-gray-600">
            <p>{{ __('Click on the highlighted areas to reveal the answers') }}</p>
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
const occlusionData = @json($flashcard->occlusion_data ?? []);
let revealedOcclusions = [];
let totalOcclusions = occlusionData.length;

function revealOcclusion(index) {
    const overlay = document.querySelector(`[data-occlusion-index="${index}"]`);
    if (!overlay || revealedOcclusions.includes(index)) {
        return;
    }
    
    // Add to revealed list
    revealedOcclusions.push(index);
    
    // Get the answer for this occlusion
    const occlusion = occlusionData[index];
    const answer = occlusion.answer || `{{ $flashcard->answer }}`;
    
    // Create answer display
    overlay.innerHTML = `
        <div class="revealed-answer text-center p-2 bg-white rounded shadow-lg border-2 border-green-400" 
             style="max-width: ${overlay.offsetWidth - 8}px; font-size: 12px; line-height: 1.2;">
            <span class="text-green-700 font-semibold">${answer}</span>
        </div>
    `;
    
    // Update overlay styling
    overlay.style.background = 'rgba(34, 197, 94, 0.2)';
    overlay.style.cursor = 'default';
    overlay.style.transform = 'scale(1.05)';
    
    // Check if all occlusions are revealed
    if (revealedOcclusions.length >= totalOcclusions) {
        setTimeout(() => {
            showOcclusionFeedback(true);
        }, 500);
    }
}

function showOcclusionFeedback(allRevealed) {
    const feedbackContainer = document.querySelector('.feedback-content');
    const feedbackMessage = document.querySelector('.feedback-message');
    
    if (allRevealed) {
        feedbackMessage.innerHTML = `
            <div class="text-green-800 bg-green-100 border-green-400 border rounded p-4">
                <div class="text-2xl mb-2">🎉</div>
                <div class="font-semibold">${'{{ __("All areas revealed!") }}'}</div>
                <div class="text-sm mt-2">${'{{ __("You have explored all the hidden information.") }}'}</div>
            </div>
        `;
    } else {
        feedbackMessage.innerHTML = `
            <div class="text-blue-800 bg-blue-100 border-blue-400 border rounded p-4">
                <div class="text-2xl mb-2">👁️</div>
                <div class="font-semibold">${'{{ __("Keep exploring!") }}'}</div>
                <div class="text-sm mt-2">${revealedOcclusions.length} / ${totalOcclusions} ${'{{ __("areas revealed") }}'}</div>
            </div>
        `;
    }
    
    feedbackContainer.style.display = 'block';
    
    // Enable rating buttons when all are revealed
    if (allRevealed) {
        setTimeout(() => {
            window.flashcardAnswered = { 
                revealedOcclusions, 
                totalOcclusions, 
                allRevealed: true 
            };
        }, 1000);
    }
}

// Auto-reveal instruction for kids mode
document.addEventListener('DOMContentLoaded', function() {
    if (totalOcclusions === 0) {
        // No occlusions, show basic feedback
        setTimeout(() => {
            showOcclusionFeedback(true);
        }, 500);
    }
});
</script>

<style>
.occlusion-overlay:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 12px rgba(0,0,0,0.3) !important;
}

.revealed-answer {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.image-container {
    display: inline-block;
}

#occlusion-image {
    display: block;
}
</style>