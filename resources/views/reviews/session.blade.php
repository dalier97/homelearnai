{{-- Review Session Modal Content --}}
<div class="review-session-interface fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-40 relative" data-testid="review-session-interface">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto relative z-50" data-testid="modal-content">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Review Session</h2>
                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $child->name }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500">
                        {{ $reviewQueue->count() }} cards remaining
                    </span>
                    <button onclick="closeReviewSession()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="px-6 py-2 bg-gray-50">
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $totalCards = 20; // Max cards per session
                    $remaining = $reviewQueue->count();
                    $completed = $totalCards - $remaining;
                    $progressPercent = $totalCards > 0 ? ($completed / $totalCards) * 100 : 0;
                @endphp
                <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>

        {{-- Review Card Content --}}
        <div id="review-card-container" class="p-6">
            @if($currentReview)
                @include('reviews.partials.review-card', ['review' => $currentReview, 'child' => $child])
            @else
                <div class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-green-500">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Session Complete!</h3>
                    <p class="mt-1 text-gray-500">Great job! All reviews are done for now.</p>
                    <div class="mt-6">
                        <button onclick="closeReviewSession()" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Close Session
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Handle review result submission
function processReviewResult(reviewId, result) {
    fetch(`/reviews/process/${reviewId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            result: result
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show result feedback briefly
            showResultFeedback(data.result, result);
            
            // Load next review after delay
            setTimeout(() => {
                if (data.session_complete) {
                    showSessionComplete();
                } else if (data.next_review) {
                    loadNextReview(data.next_review.id);
                }
            }, 1500);
        }
    })
    .catch(error => {
        console.error(window.__('Error processing review:'), error);
    });
}

function showResultFeedback(result, buttonPressed) {
    const container = document.getElementById('review-card-container');
    const feedbackColors = {
        'again': 'bg-red-100 text-red-800',
        'hard': 'bg-orange-100 text-orange-800', 
        'good': 'bg-green-100 text-green-800',
        'easy': 'bg-blue-100 text-blue-800'
    };
    
    const feedbackMessages = {
        'again': 'Will review again soon',
        'hard': 'Reviewing sooner than normal',
        'good': 'Good job! Normal spacing',
        'easy': 'Easy! Increased spacing'
    };
    
    container.innerHTML = `
        <div class="text-center py-12">
            <div class="mx-auto h-16 w-16 ${feedbackColors[buttonPressed]} rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl font-bold">${buttonPressed.charAt(0).toUpperCase()}</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">${feedbackMessages[buttonPressed]}</h3>
            <div class="text-sm text-gray-600 space-y-1">
                <p>Next review: ${result.next_due}</p>
                <p>Interval: ${result.old_interval}d → ${result.new_interval}d</p>
                <p>Ease factor: ${result.old_ease_factor} → ${result.new_ease_factor}</p>
            </div>
        </div>
    `;
}

function loadNextReview(reviewId) {
    fetch(`/reviews/review/${reviewId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('review-card-container').innerHTML = html;
            // Update progress bar
            updateProgressBar();
        });
}

function showSessionComplete() {
    const container = document.getElementById('review-card-container');
    container.innerHTML = `
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-green-500">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Session Complete!</h3>
            <p class="mt-1 text-gray-500">Great job! All reviews are done for now.</p>
            <div class="mt-6">
                <button onclick="closeReviewSession()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    Close Session
                </button>
            </div>
        </div>
    `;
    
    // Update progress to 100%
    const progressBar = document.querySelector('.bg-blue-500');
    if (progressBar) {
        progressBar.style.width = '100%';
    }
}

function updateProgressBar() {
    // This would need to be implemented to track actual progress
    // For now, it's handled by the initial calculation
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    
    const reviewId = document.querySelector('[data-review-id]')?.getAttribute('data-review-id');
    if (!reviewId) return;
    
    switch(e.key) {
        case '1':
            e.preventDefault();
            processReviewResult(reviewId, 'again');
            break;
        case '2':
            e.preventDefault();
            processReviewResult(reviewId, 'hard');
            break;
        case '3':
            e.preventDefault();
            processReviewResult(reviewId, 'good');
            break;
        case '4':
            e.preventDefault();
            processReviewResult(reviewId, 'easy');
            break;
        case 'Escape':
            e.preventDefault();
            closeReviewSession();
            break;
    }
});
</script>