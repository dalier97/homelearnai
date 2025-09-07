@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center mb-6">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1a1 1 0 00-1-1H7a1 1 0 00-1 1v1a2 2 0 002 2zM9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('kids_mode_settings') }}</h1>
                    <p class="text-gray-600">{{ __('Secure your parental controls with a 4-digit PIN') }}</p>
                </div>
            </div>

            <!-- Current PIN Status -->
            <div class="mb-6">
                <div class="flex items-center p-4 rounded-lg {{ $has_pin_setup ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
                    @if($has_pin_setup)
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 font-medium">{{ __('pin_is_set') }}</span>
                    @else
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-yellow-800 font-medium">{{ __('pin_not_set') }}</span>
                    @endif
                </div>
            </div>

            <!-- PIN Setup/Update Form -->
            <div class="border rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $has_pin_setup ? __('update_kids_mode_pin') : __('set_kids_mode_pin') }}
                </h2>
                
                <div id="pin-messages" class="mb-4"></div>

                <form id="pin-form" 
                      hx-post="{{ route('kids-mode.pin.update') }}" 
                      hx-target="#pin-messages" 
                      hx-swap="innerHTML"
                      hx-trigger="submit"
                      hx-on::after-request="handlePinResponse(event)">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('new_pin') }}
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       id="pin" 
                                       name="pin" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-xl font-mono tracking-widest"
                                       maxlength="4" 
                                       autocomplete="off"
                                       autocapitalize="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       placeholder="••••">
                                <button type="button" 
                                        id="toggle-pin" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                        aria-label="{{ __('Show/hide PIN') }}">
                                    <svg id="eye-open" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="eye-closed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L12 12m-3.878-3.122L12 12m0 0l6.878 6.878M12 12L9.878 9.878"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ __('pin_must_be_4_digits') }}</p>
                        </div>

                        <div>
                            <label for="pin_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('confirm_pin') }}
                            </label>
                            <input type="password" 
                                   id="pin_confirmation" 
                                   name="pin_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-xl font-mono tracking-widest"
                                   maxlength="4" 
                                   autocomplete="off"
                                   autocapitalize="off"
                                   autocorrect="off"
                                   spellcheck="false"
                                   placeholder="••••">
                            <p class="text-xs text-gray-500 mt-1">{{ __('Re-enter the same 4-digit PIN') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-4">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ $has_pin_setup ? __('update_kids_mode_pin') : __('set_kids_mode_pin') }}
                        </button>
                        
                        @if($has_pin_setup)
                        <button type="button" 
                                id="reset-pin-btn"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                hx-post="{{ route('kids-mode.pin.reset') }}"
                                hx-target="#pin-messages"
                                hx-confirm="{{ __('Are you sure you want to reset your PIN? This will remove PIN protection from Kids Mode.') }}">
                            {{ __('reset_pin') }}
                        </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Security Information -->
            <div class="mt-8 p-6 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">{{ __('Security Information') }}</h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('Your PIN is encrypted and securely stored') }}
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('After 5 failed attempts, PIN entry is locked for 5 minutes') }}
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('Only use digits 0-9 for your PIN') }}
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('Choose a PIN that children cannot easily guess') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for PIN form handling -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pinInput = document.getElementById('pin');
    const pinConfirmInput = document.getElementById('pin_confirmation');
    const toggleButton = document.getElementById('toggle-pin');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');

    // Only allow numeric input
    function enforceNumericInput(event) {
        if (event.type === 'keypress') {
            const charCode = event.which ? event.which : event.keyCode;
            if (charCode < 48 || charCode > 57) {
                event.preventDefault();
            }
        } else if (event.type === 'input') {
            event.target.value = event.target.value.replace(/[^0-9]/g, '');
        }
    }

    pinInput.addEventListener('keypress', enforceNumericInput);
    pinInput.addEventListener('input', enforceNumericInput);
    pinConfirmInput.addEventListener('keypress', enforceNumericInput);
    pinConfirmInput.addEventListener('input', enforceNumericInput);

    // Auto-focus next field when PIN is complete
    pinInput.addEventListener('input', function() {
        if (this.value.length === 4) {
            pinConfirmInput.focus();
        }
    });

    // Show/hide PIN toggle
    toggleButton.addEventListener('click', function() {
        const isPassword = pinInput.type === 'password';
        pinInput.type = isPassword ? 'text' : 'password';
        eyeOpen.classList.toggle('hidden', !isPassword);
        eyeClosed.classList.toggle('hidden', isPassword);
    });

    // Handle HX-Trigger events
    document.body.addEventListener('pinUpdated', function() {
        // Clear form
        document.getElementById('pin-form').reset();
        // Reload page after 2 seconds to update PIN status
        setTimeout(() => window.location.reload(), 2000);
    });
    
    // Clear form after successful submission
    window.handlePinResponse = function(event) {
        // The response is now HTML handled by HTMX
        if (event.detail.xhr.status === 200) {
            // Success handled by HX-Trigger
        }
    };
});
</script>
@endsection