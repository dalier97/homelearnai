/**
 * Flashcard Preview System
 * 
 * This module handles the flashcard preview functionality for parents.
 * CRITICAL: All preview operations are session-only with no database impact.
 */

class FlashcardPreviewSystem {
    constructor() {
        this.sessionId = null;
        this.currentIndex = 0;
        this.totalCards = 0;
        this.startTime = null;
        this.cardStartTime = null;
        
        this.init();
    }
    
    init() {
        // Initialize event listeners
        this.setupEventListeners();
        
        // Clean up any old preview sessions
        this.cleanupOldSessions();
    }
    
    setupEventListeners() {
        // Prevent accidental navigation during preview
        window.addEventListener('beforeunload', (e) => {
            if (this.sessionId && this.currentIndex < this.totalCards) {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to exit the flashcard preview?';
            }
        });
        
        // Clean up on page unload
        window.addEventListener('unload', () => {
            this.cleanup();
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (!this.sessionId) return;
            
            switch (e.key) {
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    this.handleSpaceOrEnter();
                    break;
                case 'Escape':
                    this.confirmExit();
                    break;
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    if (this.isAnswerVisible()) {
                        this.nextCard();
                    }
                    break;
            }
        });
    }
    
    /**
     * Start a new preview session
     */
    startSession(sessionId, totalCards, currentIndex = 0) {
        console.log('Starting flashcard preview session:', { sessionId, totalCards, currentIndex });
        
        this.sessionId = sessionId;
        this.totalCards = totalCards;
        this.currentIndex = currentIndex;
        this.startTime = Date.now();
        this.cardStartTime = Date.now();
        
        // Store in localStorage (no server state)
        this.saveSessionState();
        
        // Update UI
        this.updateProgressDisplay();
        
        // Log preview start (for debugging)
        this.logPreviewAction('session_start', {
            total_cards: totalCards,
            timestamp: new Date().toISOString()
        });
    }
    
    /**
     * Show the answer for the current card
     */
    showAnswer() {
        if (!this.sessionId) {
            console.warn('No active preview session');
            return;
        }
        
        // Show answer content
        const answerContent = document.querySelector('.answer-content');
        if (answerContent) {
            answerContent.style.display = 'block';
            answerContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Show feedback for interactive cards
        const feedbackContent = document.querySelector('.feedback-content');
        if (feedbackContent) {
            feedbackContent.style.display = 'block';
        }
        
        // Update button states
        const showBtn = document.getElementById('show-answer-btn');
        const nextBtn = document.getElementById('next-card-btn');
        
        if (showBtn) showBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'inline-block';
        
        // Log the action
        this.logPreviewAction('show_answer', {
            card_index: this.currentIndex,
            time_to_show: Date.now() - this.cardStartTime
        });
    }
    
    /**
     * Move to the next card in the preview
     */
    async nextCard() {
        if (!this.sessionId) {
            console.warn('No active preview session');
            return;
        }
        
        const timeSpent = Date.now() - this.cardStartTime;
        
        try {
            // Submit current card data (session-only)
            await this.submitPreviewAnswer({
                time_spent: timeSpent,
                is_correct: window.flashcardAnswered?.isCorrect,
                user_answer: window.flashcardAnswered?.userAnswer,
                selected_choices: window.flashcardAnswered?.selectedChoices,
                cloze_answers: window.flashcardAnswered?.clozeAnswers
            });
            
            // Move to next card
            this.currentIndex++;
            
            if (this.currentIndex >= this.totalCards) {
                // Session complete
                this.completeSession();
                return;
            }
            
            // Load next card
            await this.loadNextCard();
            
        } catch (error) {
            console.error('Error proceeding to next card:', error);
            this.showError('Failed to load next card. Please try again.');
        }
    }
    
    /**
     * Submit preview answer (stored in session only)
     */
    async submitPreviewAnswer(answerData) {
        const response = await fetch(`/preview/session/${this.sessionId}/answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(answerData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to submit preview answer');
        }
        
        const result = await response.json();
        
        // Log the preview answer (debugging only)
        this.logPreviewAction('answer_submitted', {
            card_index: this.currentIndex,
            ...answerData,
            server_response: result.success
        });
        
        return result;
    }
    
    /**
     * Load the next card in the preview
     */
    async loadNextCard() {
        const response = await fetch(`/preview/session/${this.sessionId}/next`);
        
        if (!response.ok) {
            throw new Error('Failed to load next card');
        }
        
        const result = await response.json();
        
        if (result.session_complete) {
            this.completeSession();
            return;
        }
        
        // For now, we'll reload the page to show the next card
        // In a production app, you'd want to dynamically update the DOM
        window.location.reload();
    }
    
    /**
     * Complete the preview session
     */
    completeSession() {
        if (!this.sessionId) return;
        
        this.logPreviewAction('session_complete', {
            total_time: Date.now() - this.startTime,
            cards_reviewed: this.currentIndex
        });
        
        // Navigate to completion page
        window.location.href = `/preview/session/${this.sessionId}/end`;
    }
    
    /**
     * Handle space bar or enter key
     */
    handleSpaceOrEnter() {
        const showBtn = document.getElementById('show-answer-btn');
        const nextBtn = document.getElementById('next-card-btn');
        
        if (showBtn && showBtn.style.display !== 'none') {
            this.showAnswer();
        } else if (nextBtn && nextBtn.style.display !== 'none') {
            this.nextCard();
        }
    }
    
    /**
     * Check if answer is currently visible
     */
    isAnswerVisible() {
        const answerContent = document.querySelector('.answer-content');
        return answerContent && answerContent.style.display !== 'none';
    }
    
    /**
     * Update progress display
     */
    updateProgressDisplay() {
        const progressBar = document.getElementById('progress-bar');
        const currentCardNum = document.getElementById('current-card-number');
        
        if (progressBar) {
            const progressPercent = Math.round((this.currentIndex / this.totalCards) * 100);
            progressBar.style.width = progressPercent + '%';
        }
        
        if (currentCardNum) {
            currentCardNum.textContent = this.currentIndex + 1;
        }
    }
    
    /**
     * Save session state to localStorage
     */
    saveSessionState() {
        if (!this.sessionId) return;
        
        const sessionData = {
            sessionId: this.sessionId,
            currentIndex: this.currentIndex,
            totalCards: this.totalCards,
            startTime: this.startTime,
            cardStartTime: this.cardStartTime
        };
        
        localStorage.setItem(`flashcard_preview_${this.sessionId}`, JSON.stringify(sessionData));
    }
    
    /**
     * Load session state from localStorage
     */
    loadSessionState(sessionId) {
        const stored = localStorage.getItem(`flashcard_preview_${sessionId}`);
        if (!stored) return null;
        
        try {
            return JSON.parse(stored);
        } catch (error) {
            console.warn('Failed to parse stored session data:', error);
            return null;
        }
    }
    
    /**
     * Log preview action (for debugging, not stored in database)
     */
    logPreviewAction(action, data = {}) {
        if (console && console.groupCollapsed) {
            console.groupCollapsed(`Preview Action: ${action}`);
            console.log('Session ID:', this.sessionId);
            console.log('Action Data:', data);
            console.log('Timestamp:', new Date().toISOString());
            console.groupEnd();
        }
    }
    
    /**
     * Show error message to user
     */
    showError(message) {
        // You could integrate with your app's toast system here
        alert(message);
    }
    
    /**
     * Confirm exit from preview
     */
    confirmExit() {
        if (confirm('Are you sure you want to exit the flashcard preview?')) {
            this.exitPreview();
        }
    }
    
    /**
     * Exit preview session
     */
    exitPreview() {
        this.cleanup();
        
        // Navigate back to unit page or wherever appropriate
        // You might want to get the unit ID from the session data
        history.back();
    }
    
    /**
     * Clean up session data
     */
    cleanup() {
        if (this.sessionId) {
            localStorage.removeItem(`flashcard_preview_${this.sessionId}`);
            this.logPreviewAction('session_cleanup');
        }
        
        this.sessionId = null;
        this.currentIndex = 0;
        this.totalCards = 0;
        this.startTime = null;
        this.cardStartTime = null;
    }
    
    /**
     * Clean up old preview sessions
     */
    cleanupOldSessions() {
        const keys = Object.keys(localStorage);
        const previewKeys = keys.filter(key => key.startsWith('flashcard_preview_'));
        
        previewKeys.forEach(key => {
            const stored = localStorage.getItem(key);
            if (!stored) return;
            
            try {
                const data = JSON.parse(stored);
                const age = Date.now() - (data.startTime || 0);
                
                // Clean up sessions older than 24 hours
                if (age > 24 * 60 * 60 * 1000) {
                    localStorage.removeItem(key);
                    console.log('Cleaned up old preview session:', key);
                }
            } catch (error) {
                // Remove malformed data
                localStorage.removeItem(key);
            }
        });
    }
}

// Global functions for backwards compatibility
window.flashcardPreview = new FlashcardPreviewSystem();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FlashcardPreviewSystem;
}