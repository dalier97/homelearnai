/**
 * Kids Mode UI JavaScript
 * Handles kids mode enter/exit UI interactions, loading states, and security features
 */

class KidsModeUI {
    constructor() {
        this.initEnterButtonHandlers();
        this.initBackButtonPrevention();
        this.initKidsModeIndicator();
    }

    /**
     * Initialize Enter Kids Mode button handlers
     */
    initEnterButtonHandlers() {
        // Handle kids mode enter button clicks
        document.addEventListener('click', (event) => {
            if (event.target.closest('.kids-mode-enter-btn')) {
                const button = event.target.closest('.kids-mode-enter-btn');
                this.handleEnterKidsMode(button);
            }
        });

        // Handle HTMX events for kids mode entry
        document.body.addEventListener('htmx:beforeRequest', (event) => {
            if (event.detail.elt.classList.contains('kids-mode-enter-btn')) {
                this.showEnterLoading(event.detail.elt);
            }
        });

        document.body.addEventListener('htmx:afterRequest', (event) => {
            if (event.detail.elt.classList.contains('kids-mode-enter-btn')) {
                this.handleEnterResponse(event);
            }
        });
    }

    /**
     * Handle kids mode entry button click
     */
    handleEnterKidsMode(button) {
        const childName = this.getChildNameFromButton(button);
        
        // Disable button to prevent double-clicks
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');
        
        // Show loading animation
        const indicator = button.querySelector('.htmx-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';
        }

        console.log(`Entering kids mode for: ${childName}`);
    }

    /**
     * Show loading state for enter button
     */
    showEnterLoading(button) {
        const text = button.querySelector('span:not(.htmx-indicator span)');
        const originalText = text ? text.textContent : '';
        
        if (text) {
            text.textContent = window.__('entering_kids_mode', {}, 'Entering Kids Mode...');
            text.dataset.originalText = originalText;
        }

        // Animate the button
        button.style.transform = 'scale(0.98)';
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 150);
    }

    /**
     * Handle response from kids mode enter request
     */
    handleEnterResponse(event) {
        const button = event.detail.elt;
        const xhr = event.detail.xhr;

        // Hide loading indicator
        const indicator = button.querySelector('.htmx-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }

        // Restore button state
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed');

        const text = button.querySelector('span:not(.htmx-indicator span)');
        if (text && text.dataset.originalText) {
            text.textContent = text.dataset.originalText;
            delete text.dataset.originalText;
        }

        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    this.showToast(response.message, 'success');
                }
                
                // Redirect will happen automatically via HTMX redirect
                console.log('Kids mode entry successful');
            } catch (e) {
                console.error('Error parsing kids mode response:', e);
                this.showToast(window.__('An error occurred. Please try again.'), 'error');
            }
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                this.showToast(response.error || window.__('Failed to enter kids mode'), 'error');
            } catch (e) {
                this.showToast(window.__('Failed to enter kids mode'), 'error');
            }
        }
    }

    /**
     * Get child name from button context
     */
    getChildNameFromButton(button) {
        // Try to get child name from the parent card
        const childCard = button.closest('.bg-white');
        if (childCard) {
            const nameElement = childCard.querySelector('h3');
            if (nameElement) {
                return nameElement.textContent.trim();
            }
        }
        return 'Child';
    }

    /**
     * Prevent browser back button in kids mode
     */
    initBackButtonPrevention() {
        // Only apply if kids mode is active
        if (this.isKidsModeActive()) {
            // Add state to history to prevent back navigation
            history.pushState(null, null, location.href);
            
            window.addEventListener('popstate', (event) => {
                // Prevent going back
                history.pushState(null, null, location.href);
                
                // Show message to child
                this.showToast(
                    window.__('ask_parent_for_help', {}, 'Ask a parent to help exit Kids Mode'),
                    'info',
                    5000
                );
            });

            // Also prevent some keyboard shortcuts that could bypass kids mode
            document.addEventListener('keydown', (event) => {
                // Prevent Alt+Left (back), Alt+Right (forward), F5 (refresh), Ctrl+R (refresh)
                if ((event.altKey && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) ||
                    (event.key === 'F5') ||
                    (event.ctrlKey && event.key === 'r') ||
                    (event.ctrlKey && event.key === 'R')) {
                    event.preventDefault();
                    this.showToast(
                        window.__('ask_parent_for_help', {}, 'Ask a parent to help exit Kids Mode'),
                        'info',
                        3000
                    );
                }
            });
        }
    }

    /**
     * Initialize kids mode indicator animations and interactions
     */
    initKidsModeIndicator() {
        const indicator = document.querySelector('[data-testid="kids-mode-indicator"]');
        if (indicator) {
            // Add pulse effect every 30 seconds as a gentle reminder
            setInterval(() => {
                if (this.isKidsModeActive()) {
                    indicator.classList.remove('animate-pulse');
                    setTimeout(() => {
                        indicator.classList.add('animate-pulse');
                    }, 100);
                }
            }, 30000);

            // Add click to expand/collapse functionality
            let isExpanded = false;
            const toggleExpand = () => {
                if (isExpanded) {
                    indicator.style.transform = 'scale(1)';
                    indicator.style.opacity = '1';
                    isExpanded = false;
                } else {
                    indicator.style.transform = 'scale(1.05)';
                    indicator.style.opacity = '0.95';
                    isExpanded = true;
                    setTimeout(toggleExpand, 2000); // Auto-collapse after 2 seconds
                }
            };

            indicator.addEventListener('click', toggleExpand);
        }
    }

    /**
     * Check if kids mode is currently active
     */
    isKidsModeActive() {
        return document.querySelector('[data-testid="kids-mode-indicator"]') !== null;
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info', duration = 3000) {
        // Use the global showToast function if available
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback toast implementation
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500',
            };

            toast.className = `${colors[type] || colors.info} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full fixed top-4 right-4 z-50`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => toast.classList.remove('translate-x-full'), 10);
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    }

    /**
     * Handle PIN exit screen if it exists
     */
    initPinExitScreen() {
        // This method can be called from the existing kids-mode.js
        // to integrate with the PIN entry functionality
        const pinForm = document.getElementById('pin-form');
        if (pinForm) {
            // Add smooth transitions for PIN entry
            const pinDigits = document.querySelectorAll('.pin-digit');
            pinDigits.forEach((digit, index) => {
                digit.style.transitionDelay = `${index * 50}ms`;
            });

            // Add haptic feedback simulation for touch devices
            const keypadButtons = document.querySelectorAll('[data-digit]');
            keypadButtons.forEach(button => {
                button.addEventListener('touchstart', () => {
                    if (navigator.vibrate) {
                        navigator.vibrate(50); // Short vibration
                    }
                });
            });
        }
    }
}

// Initialize kids mode UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.kidsModeUI = new KidsModeUI();
    
    // Initialize PIN exit screen if present
    if (document.getElementById('pin-form')) {
        window.kidsModeUI.initPinExitScreen();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KidsModeUI;
}