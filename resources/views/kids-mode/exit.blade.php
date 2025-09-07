<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ __('exit_kids_mode') }} - {{ __('Homeschool Hub') }}</title>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .pin-digit {
            transition: all 0.2s ease;
        }
        
        .pin-digit.filled {
            background-color: #3b82f6;
            color: white;
            transform: scale(1.05);
        }
        
        .keypad-button {
            transition: all 0.15s ease;
            user-select: none;
        }
        
        .keypad-button:active {
            transform: scale(0.95);
        }
        
        .keypad-button:hover {
            transform: scale(1.05);
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 20%, 40%, 60%, 80%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        }
        
        .pulse-error {
            animation: pulseError 0.5s ease-in-out;
        }
        
        @keyframes pulseError {
            0%, 100% { background-color: #fef2f2; border-color: #fca5a5; }
            50% { background-color: #fee2e2; border-color: #f87171; }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Main Container -->
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <!-- Child Information -->
            @if($child)
            <div class="mb-6">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">
                    {{ __('Learning time with :name', ['name' => $child->name]) }}
                </h2>
                <p class="text-blue-100">{{ __('Ask a parent to help exit Kids Mode') }}</p>
            </div>
            @endif

            <!-- Title -->
            <h1 class="text-3xl font-bold text-white mb-2">{{ __('enter_parent_pin') }}</h1>
            <p class="text-blue-100">{{ __('Enter the 4-digit PIN to exit Kids Mode') }}</p>
        </div>

        <!-- PIN Entry Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            @if(!$has_pin_setup)
                <!-- No PIN Setup Message -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('PIN Not Set') }}</h3>
                    <p class="text-gray-600 mb-6">{{ __('A parent needs to set up a PIN first') }}</p>
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-full font-semibold hover:bg-blue-700 transition-colors">
                        {{ __('Go to Dashboard') }}
                    </a>
                </div>
            @elseif($is_locked)
                <!-- Account Locked Message -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-red-900 mb-2">{{ __('Account Locked') }}</h3>
                    <p class="text-red-700 mb-2">{{ __('too_many_attempts') }}</p>
                    @if($lockout_time)
                    <p class="text-sm text-red-600 mb-6">
                        {{ __('Try again after :time', ['time' => $lockout_time->format('H:i')]) }}
                    </p>
                    @endif
                    <button type="button" 
                            class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-full font-semibold cursor-not-allowed opacity-50">
                        {{ __('Locked') }}
                    </button>
                </div>
            @else
                <!-- PIN Entry Interface -->
                <div id="pin-entry-container">
                    <!-- Error Message -->
                    <div id="error-message" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span id="error-text" class="text-red-700"></span>
                        </div>
                    </div>

                    <!-- PIN Display -->
                    <div class="flex justify-center mb-8">
                        <div class="flex space-x-3">
                            @for($i = 0; $i < 4; $i++)
                            <div class="pin-digit w-14 h-14 bg-gray-100 border-2 border-gray-300 rounded-xl flex items-center justify-center text-2xl font-bold text-gray-800" data-index="{{ $i }}">
                                <span class="dot hidden">•</span>
                                <span class="number"></span>
                            </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Numeric Keypad -->
                    <div class="grid grid-cols-3 gap-4 mb-6" id="keypad">
                        @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $digit)
                        <button type="button" 
                                class="keypad-button h-16 bg-gray-50 hover:bg-gray-100 border-2 border-gray-200 rounded-xl text-2xl font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                data-digit="{{ $digit }}">
                            {{ $digit }}
                        </button>
                        @endforeach
                        
                        <!-- Bottom row: Clear, 0, Backspace -->
                        <button type="button" 
                                id="clear-btn"
                                class="keypad-button h-16 bg-red-50 hover:bg-red-100 border-2 border-red-200 rounded-xl font-semibold text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Clear') }}
                        </button>
                        
                        <button type="button" 
                                class="keypad-button h-16 bg-gray-50 hover:bg-gray-100 border-2 border-gray-200 rounded-xl text-2xl font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                data-digit="0">
                            0
                        </button>
                        
                        <button type="button" 
                                id="backspace-btn"
                                class="keypad-button h-16 bg-yellow-50 hover:bg-yellow-100 border-2 border-yellow-200 rounded-xl font-semibold text-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col space-y-3">
                        <button type="button" 
                                id="submit-pin-btn"
                                class="w-full py-4 bg-blue-600 text-white rounded-xl font-semibold text-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('exit_kids_mode') }}
                        </button>
                        
                        <button type="button" 
                                onclick="history.back()"
                                class="w-full py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            {{ __('back_to_learning') }}
                        </button>
                    </div>

                    <!-- Attempts Remaining -->
                    @if($attempts_remaining < 5)
                    <div class="text-center mt-4">
                        <p class="text-sm text-yellow-600">
                            {{ __('Attempts remaining') }}: <strong>{{ $attempts_remaining }}</strong>
                        </p>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Hidden form for HTMX submission -->
    <form id="pin-form" hx-post="{{ route('kids-mode.exit.validate') }}" 
          hx-trigger="submit" 
          hx-on::after-request="handlePinResponse(event)" 
          style="display: none;">
        @csrf
        <input type="hidden" id="pin-input" name="pin" value="">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        function addDigit(digit) {
            if (currentPin.length < 4) {
                currentPin.push(digit);
                updatePinDisplay();
                
                // Auto-submit when PIN is complete
                if (currentPin.length === 4) {
                    setTimeout(() => submitPin(), 300);
                }
            }
        }

        // Remove last digit
        function removeDigit() {
            if (currentPin.length > 0) {
                currentPin.pop();
                updatePinDisplay();
                hideError();
            }
        }

        // Clear all digits
        function clearPin() {
            currentPin = [];
            updatePinDisplay();
            hideError();
        }

        // Update visual PIN display
        function updatePinDisplay() {
            pinDigits.forEach((digit, index) => {
                const dot = digit.querySelector('.dot');
                const number = digit.querySelector('.number');
                
                if (index < currentPin.length) {
                    digit.classList.add('filled');
                    dot.classList.remove('hidden');
                    number.textContent = '';
                } else {
                    digit.classList.remove('filled');
                    dot.classList.add('hidden');
                    number.textContent = '';
                }
            });
            
            submitBtn.disabled = currentPin.length !== 4;
        }

        // Submit PIN
        function submitPin() {
            if (currentPin.length === 4) {
                pinInput.value = currentPin.join('');
                pinForm.dispatchEvent(new Event('submit'));
            }
        }

        // Show error message
        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            
            // Add shake animation
            const container = document.getElementById('pin-entry-container');
            container.classList.add('shake');
            setTimeout(() => container.classList.remove('shake'), 500);
            
            // Add pulse error to PIN digits
            pinDigits.forEach(digit => {
                digit.classList.add('pulse-error');
                setTimeout(() => digit.classList.remove('pulse-error'), 500);
            });
            
            // Clear PIN after error
            setTimeout(() => clearPin(), 1000);
        }

        // Hide error message
        function hideError() {
            errorMessage.classList.add('hidden');
        }

        // Event listeners for keypad
        keypadButtons.forEach(button => {
            button.addEventListener('click', function() {
                addDigit(this.dataset.digit);
            });
        });

        clearBtn.addEventListener('click', clearPin);
        backspaceBtn.addEventListener('click', removeDigit);
        submitBtn.addEventListener('click', submitPin);

        // Keyboard support
        document.addEventListener('keydown', function(event) {
            if (event.key >= '0' && event.key <= '9') {
                addDigit(event.key);
            } else if (event.key === 'Backspace') {
                removeDigit();
            } else if (event.key === 'Enter' && currentPin.length === 4) {
                submitPin();
            } else if (event.key === 'Escape') {
                clearPin();
            }
        });

        // Handle HTMX response
        window.handlePinResponse = function(event) {
            const xhr = event.detail.xhr;
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.redirect_url) {
                        // Success - redirect
                        window.location.href = response.redirect_url;
                    }
                } catch (e) {
                    showError('{{ __("An error occurred. Please try again.") }}');
                }
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showError(response.error || '{{ __("Incorrect PIN. Try again.") }}');
                    
                    // Handle lockout
                    if (xhr.status === 429) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } catch (e) {
                    showError('{{ __("incorrect_pin") }}');
                }
            }
        };

        // Initialize
        updatePinDisplay();
    });

    // === KIDS MODE SECURITY PROTECTIONS ===
    
    // 1. Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // 2. Disable developer tools shortcuts
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+I (Chrome DevTools)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+J (Chrome Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+C (Chrome Inspect)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            return false;
        }
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            return false;
        }
        // Ctrl+S (Save Page)
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            return false;
        }
        // Ctrl+A (Select All)
        if (e.ctrlKey && e.keyCode === 65) {
            e.preventDefault();
            return false;
        }
        // Ctrl+P (Print)
        if (e.ctrlKey && e.keyCode === 80) {
            e.preventDefault();
            return false;
        }
    });

    // 3. Disable text selection
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
    });

    // 4. Disable drag and drop
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });

    // 5. Detect developer tools opening (basic detection)
    let devtools = {
        open: false,
        orientation: null
    };
    
    const threshold = 160;
    
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                // Redirect to child dashboard when dev tools detected
                window.location.href = '{{ route("dashboard.child-today", ["child_id" => $child->id ?? 1]) }}';
            }
        } else {
            devtools.open = false;
        }
    }, 500);

    // 6. Monitor for console usage attempts
    let consoleMethods = ['log', 'debug', 'info', 'warn', 'error', 'assert', 'clear'];
    consoleMethods.forEach(method => {
        let original = console[method];
        console[method] = function() {
            // Log security violation
            fetch('{{ route("kids-mode.exit.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    security_event: 'console_access_attempt',
                    method: method,
                    timestamp: new Date().toISOString()
                })
            }).catch(() => {});
            return original.apply(console, arguments);
        };
    });

    // 7. Disable common bypass attempts
    Object.defineProperty(window, 'console', {
        value: console,
        writable: false,
        configurable: false
    });

    // 8. Monitor for script injection attempts
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const element = originalCreateElement.call(document, tagName);
        if (tagName.toLowerCase() === 'script') {
            // Log security violation and block
            fetch('{{ route("kids-mode.exit.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    security_event: 'script_injection_attempt',
                    timestamp: new Date().toISOString()
                })
            }).catch(() => {});
            return document.createElement('div'); // Return harmless element
        }
        return element;
    };

    // 9. Clear any existing timers/intervals that might be used for attacks
    for (let i = 1; i < 99999; i++) {
        window.clearInterval(i);
        window.clearTimeout(i);
    }

    // 10. Hide cursor trail and disable certain mouse events
    document.addEventListener('mousemove', function(e) {
        // Prevent mouse coordinate tracking
        if (e.ctrlKey || e.altKey || e.metaKey) {
            e.preventDefault();
            return false;
        }
    });

    // 11. Session timeout protection
    let lastActivity = Date.now();
    let warningShown = false;
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
    const WARNING_TIME = 25 * 60 * 1000;   // 25 minutes
    
    document.addEventListener('click', () => lastActivity = Date.now());
    document.addEventListener('keypress', () => lastActivity = Date.now());
    document.addEventListener('touchstart', () => lastActivity = Date.now());
    
    setInterval(function() {
        const inactiveTime = Date.now() - lastActivity;
        
        if (inactiveTime > SESSION_TIMEOUT) {
            // Auto-redirect to dashboard after timeout
            window.location.href = '{{ route("dashboard") }}';
        } else if (inactiveTime > WARNING_TIME && !warningShown) {
            warningShown = true;
            // Could show a warning, but for kids mode we'll just silently handle
        }
        
        if (inactiveTime < WARNING_TIME) {
            warningShown = false;
        }
    }, 60000); // Check every minute
    </script>
</body>
</html>