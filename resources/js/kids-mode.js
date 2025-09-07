/**
 * Kids Mode PIN Management JavaScript Components
 * Provides touch-friendly numeric keypad and PIN entry functionality
 */

class KidsModeComponents {
    constructor() {
        this.init();
    }

    init() {
        // Initialize components based on current page
        if (document.getElementById('pin-entry-container')) {
            this.initExitScreen();
        }
        
        if (document.getElementById('pin-form')) {
            this.initSettingsForm();
        }
    }

    /**
     * Initialize the Kids Mode exit screen with numeric keypad
     */
    initExitScreen() {
        const pinDigits = document.querySelectorAll('.pin-digit');
        const keypadButtons = document.querySelectorAll('[data-digit]');
        const clearBtn = document.getElementById('clear-btn');
        const backspaceBtn = document.getElementById('backspace-btn');
        const submitBtn = document.getElementById('submit-pin-btn');
        const errorMessage = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const pinForm = document.getElementById('pin-form');
        const pinInput = document.getElementById('pin-input');
        
        let currentPin = [];

        // Add digit to PIN
        const addDigit = (digit) => {
            if (currentPin.length < 4) {
                currentPin.push(digit);
                this.updatePinDisplay(pinDigits, currentPin);
                
                // Auto-submit when PIN is complete
                if (currentPin.length === 4) {
                    setTimeout(() => this.submitPin(pinInput, pinForm, currentPin), 300);
                }
            }
        };

        // Remove last digit
        const removeDigit = () => {
            if (currentPin.length > 0) {
                currentPin.pop();
                this.updatePinDisplay(pinDigits, currentPin);
                this.hideError(errorMessage);
            }
        };

        // Clear all digits
        const clearPin = () => {
            currentPin = [];
            this.updatePinDisplay(pinDigits, currentPin);
            this.hideError(errorMessage);
        };

        // Event listeners for keypad
        keypadButtons.forEach(button => {
            button.addEventListener('click', function() {
                addDigit(this.dataset.digit);
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', clearPin);
        }

        if (backspaceBtn) {
            backspaceBtn.addEventListener('click', removeDigit);
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitPin(pinInput, pinForm, currentPin));
        }

        // Keyboard support
        document.addEventListener('keydown', (event) => {
            if (event.key >= '0' && event.key <= '9') {
                addDigit(event.key);
            } else if (event.key === 'Backspace') {
                removeDigit();
            } else if (event.key === 'Enter' && currentPin.length === 4) {
                this.submitPin(pinInput, pinForm, currentPin);
            } else if (event.key === 'Escape') {
                clearPin();
            }
        });

        // Handle HTMX response
        window.handlePinResponse = (event) => {
            const xhr = event.detail.xhr;
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.redirect_url) {
                        // Success - redirect
                        window.location.href = response.redirect_url;
                    }
                } catch (e) {
                    this.showError(errorMessage, errorText, 'An error occurred. Please try again.', currentPin);
                }
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    this.showError(errorMessage, errorText, response.error || 'Incorrect PIN. Try again.', currentPin);
                    
                    // Handle lockout
                    if (xhr.status === 429) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } catch (e) {
                    this.showError(errorMessage, errorText, 'Incorrect PIN. Try again.', currentPin);
                }
            }
            
            // Clear PIN after any response
            currentPin = [];
        };

        // Initialize display
        this.updatePinDisplay(pinDigits, currentPin);
    }

    /**
     * Initialize the PIN settings form
     */
    initSettingsForm() {
        const pinInput = document.getElementById('pin');
        const pinConfirmInput = document.getElementById('pin_confirmation');
        const toggleButton = document.getElementById('toggle-pin');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        // Only allow numeric input
        const enforceNumericInput = (event) => {
            if (event.type === 'keypress') {
                const charCode = event.which ? event.which : event.keyCode;
                if (charCode < 48 || charCode > 57) {
                    event.preventDefault();
                }
            } else if (event.type === 'input') {
                event.target.value = event.target.value.replace(/[^0-9]/g, '');
            }
        };

        if (pinInput) {
            pinInput.addEventListener('keypress', enforceNumericInput);
            pinInput.addEventListener('input', enforceNumericInput);

            // Auto-focus next field when PIN is complete
            pinInput.addEventListener('input', function() {
                if (this.value.length === 4 && pinConfirmInput) {
                    pinConfirmInput.focus();
                }
            });
        }

        if (pinConfirmInput) {
            pinConfirmInput.addEventListener('keypress', enforceNumericInput);
            pinConfirmInput.addEventListener('input', enforceNumericInput);
        }

        // Show/hide PIN toggle
        if (toggleButton && pinInput) {
            toggleButton.addEventListener('click', function() {
                const isPassword = pinInput.type === 'password';
                pinInput.type = isPassword ? 'text' : 'password';
                if (eyeOpen && eyeClosed) {
                    eyeOpen.classList.toggle('hidden', isPassword);
                    eyeClosed.classList.toggle('hidden', !isPassword);
                }
            });
        }

        // Handle form response
        window.handlePinResponse = (event) => {
            const response = event.detail.xhr.response;
            try {
                const data = JSON.parse(response);
                if (event.detail.xhr.status === 200 && data.message) {
                    // Success - show success message
                    document.getElementById('pin-messages').innerHTML = `
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-green-800 font-medium">${data.message}</span>
                            </div>
                        </div>
                    `;
                    // Clear form
                    document.getElementById('pin-form').reset();
                    // Reload page after 2 seconds to update PIN status
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (e) {
                // Response is not JSON, likely HTML error message from HTMX
                // The error message is already displayed by HTMX
            }
        };
    }

    /**
     * Update visual PIN display
     */
    updatePinDisplay(pinDigits, currentPin) {
        if (!pinDigits) return;

        pinDigits.forEach((digit, index) => {
            const dot = digit.querySelector('.dot');
            const number = digit.querySelector('.number');
            
            if (index < currentPin.length) {
                digit.classList.add('filled');
                if (dot) dot.classList.remove('hidden');
                if (number) number.textContent = '';
            } else {
                digit.classList.remove('filled');
                if (dot) dot.classList.add('hidden');
                if (number) number.textContent = '';
            }
        });

        // Update submit button state
        const submitBtn = document.getElementById('submit-pin-btn');
        if (submitBtn) {
            submitBtn.disabled = currentPin.length !== 4;
        }
    }

    /**
     * Submit PIN
     */
    submitPin(pinInput, pinForm, currentPin) {
        if (currentPin.length === 4 && pinInput && pinForm) {
            pinInput.value = currentPin.join('');
            pinForm.dispatchEvent(new Event('submit'));
        }
    }

    /**
     * Show error message with visual feedback
     */
    showError(errorMessage, errorText, message, currentPin) {
        if (errorText) {
            errorText.textContent = message;
        }
        
        if (errorMessage) {
            errorMessage.classList.remove('hidden');
        }
        
        // Add shake animation
        const container = document.getElementById('pin-entry-container');
        if (container) {
            container.classList.add('shake');
            setTimeout(() => container.classList.remove('shake'), 500);
        }
        
        // Add pulse error to PIN digits
        const pinDigits = document.querySelectorAll('.pin-digit');
        pinDigits.forEach(digit => {
            digit.classList.add('pulse-error');
            setTimeout(() => digit.classList.remove('pulse-error'), 500);
        });
        
        // Clear PIN after error
        setTimeout(() => {
            if (Array.isArray(currentPin)) {
                currentPin.length = 0;
                this.updatePinDisplay(pinDigits, currentPin);
            }
        }, 1000);
    }

    /**
     * Hide error message
     */
    hideError(errorMessage) {
        if (errorMessage) {
            errorMessage.classList.add('hidden');
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new KidsModeComponents();
});

// Export for manual initialization if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KidsModeComponents;
}