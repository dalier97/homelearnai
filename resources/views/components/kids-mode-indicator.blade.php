@if(session('kids_mode_active'))
    @php
        $childName = session('kids_mode_child_name', 'Child');
        $enteredAt = session('kids_mode_entered_at');
        $timeAgo = $enteredAt ? \Carbon\Carbon::parse($enteredAt)->diffForHumans() : '';
    @endphp
    <div class="fixed top-4 right-4 z-50 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg shadow-lg p-4 animate-pulse" 
         data-testid="kids-mode-indicator">
        <div class="flex items-center space-x-3">
            <!-- Kids Mode Icon -->
            <div class="flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1a1 1 0 00-1-1H7a1 1 0 00-1 1v1a2 2 0 002 2zM9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            
            <!-- Kids Mode Info -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium">{{ __('kids_mode_active') }}</p>
                <p class="text-lg font-bold truncate">{{ $childName }}</p>
                @if($timeAgo)
                    <p class="text-xs opacity-90">{{ __('Entered :time', ['time' => $timeAgo]) }}</p>
                @endif
            </div>
            
            <!-- Exit Button -->
            <div class="flex-shrink-0">
                <a href="{{ route('kids-mode.exit') }}" 
                   data-testid="exit-kids-mode-btn"
                   class="inline-flex items-center px-3 py-2 bg-white/20 hover:bg-white/30 rounded-md text-sm font-medium transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('exit_kids_mode') }}
                </a>
            </div>
        </div>
        
        <!-- Progress Bar (Optional - shows time in kids mode) -->
        <div class="mt-3 bg-white/20 rounded-full h-1">
            <div class="bg-white rounded-full h-1 animate-pulse" style="width: 60%;"></div>
        </div>
    </div>
@endif