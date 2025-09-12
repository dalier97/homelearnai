{{-- Help Tooltip Component --}}
@props([
    'id' => null,
    'title' => 'Help',
    'content' => '',
    'position' => 'top',
    'size' => 'md',
    'trigger' => 'hover',
    'icon' => 'question-mark-circle',
    'theme' => 'light'
])

@php
    $tooltipId = $id ?? 'tooltip-' . uniqid();
    $sizeClasses = [
        'sm' => 'w-48',
        'md' => 'w-64',
        'lg' => 'w-80',
        'xl' => 'w-96'
    ];
    $positionClasses = [
        'top' => 'bottom-full left-1/2 transform -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 transform -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 transform -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 transform -translate-y-1/2 ml-2'
    ];
    $themeClasses = [
        'light' => 'bg-white text-gray-800 border border-gray-200 shadow-lg',
        'dark' => 'bg-gray-800 text-white border border-gray-600',
        'kids' => 'bg-blue-50 text-blue-900 border border-blue-200 shadow-lg'
    ];
@endphp

<div class="relative inline-block" x-data="{ 
    showTooltip: false,
    toggleTooltip() {
        this.showTooltip = !this.showTooltip;
    },
    hideTooltip() {
        this.showTooltip = false;
    }
}">
    <!-- Trigger Element -->
    <button 
        type="button"
        class="inline-flex items-center justify-center w-5 h-5 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded-full transition-colors duration-200"
        @if($trigger === 'hover')
            @mouseenter="showTooltip = true"
            @mouseleave="showTooltip = false"
        @else
            @click="toggleTooltip()"
        @endif
        aria-describedby="{{ $tooltipId }}"
        data-help-trigger="{{ $tooltipId }}"
    >
        @if($icon === 'question-mark-circle')
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
        @elseif($icon === 'information-circle')
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        @elseif($icon === 'light-bulb')
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.477.859h4z"/>
            </svg>
        @endif
        <span class="sr-only">{{ $title }}</span>
    </button>

    <!-- Tooltip Content -->
    <div 
        x-show="showTooltip"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute {{ $positionClasses[$position] }} {{ $sizeClasses[$size] }} {{ $themeClasses[$theme] }} rounded-lg p-3 z-50 pointer-events-none"
        id="{{ $tooltipId }}"
        role="tooltip"
        @if($trigger === 'click')
            @click.away="hideTooltip()"
        @endif
        style="display: none;"
        x-cloak
    >
        <!-- Tooltip Arrow -->
        <div class="absolute {{ $position === 'top' ? 'top-full left-1/2 transform -translate-x-1/2' : 
                                ($position === 'bottom' ? 'bottom-full left-1/2 transform -translate-x-1/2' : 
                                ($position === 'left' ? 'left-full top-1/2 transform -translate-y-1/2' : 
                                'right-full top-1/2 transform -translate-y-1/2')) }}">
            @if($position === 'top')
                <div class="w-2 h-2 {{ str_replace('bg-', 'bg-', $themeClasses[$theme]) }} transform rotate-45 border-r border-b {{ str_contains($themeClasses[$theme], 'border-gray-200') ? 'border-gray-200' : (str_contains($themeClasses[$theme], 'border-gray-600') ? 'border-gray-600' : 'border-blue-200') }}"></div>
            @elseif($position === 'bottom')
                <div class="w-2 h-2 {{ str_replace('bg-', 'bg-', $themeClasses[$theme]) }} transform rotate-45 border-l border-t {{ str_contains($themeClasses[$theme], 'border-gray-200') ? 'border-gray-200' : (str_contains($themeClasses[$theme], 'border-gray-600') ? 'border-gray-600' : 'border-blue-200') }}"></div>
            @elseif($position === 'left')
                <div class="w-2 h-2 {{ str_replace('bg-', 'bg-', $themeClasses[$theme]) }} transform rotate-45 border-t border-r {{ str_contains($themeClasses[$theme], 'border-gray-200') ? 'border-gray-200' : (str_contains($themeClasses[$theme], 'border-gray-600') ? 'border-gray-600' : 'border-blue-200') }}"></div>
            @else
                <div class="w-2 h-2 {{ str_replace('bg-', 'bg-', $themeClasses[$theme]) }} transform rotate-45 border-b border-l {{ str_contains($themeClasses[$theme], 'border-gray-200') ? 'border-gray-200' : (str_contains($themeClasses[$theme], 'border-gray-600') ? 'border-gray-600' : 'border-blue-200') }}"></div>
            @endif
        </div>

        <!-- Tooltip Header -->
        @if($title && $title !== 'Help')
            <div class="font-medium text-sm mb-2 flex items-center">
                @if($theme === 'kids')
                    <span class="mr-1">💡</span>
                @endif
                {{ $title }}
            </div>
        @endif

        <!-- Tooltip Content -->
        <div class="text-sm leading-relaxed">
            @if($content)
                {!! nl2br(e($content)) !!}
            @else
                {{ $slot }}
            @endif
        </div>

        <!-- Close button for click trigger -->
        @if($trigger === 'click')
            <button 
                type="button"
                @click="hideTooltip()"
                class="absolute top-1 right-1 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        @endif
    </div>
</div>

{{-- Initialize help tooltips --}}
@once
<script>
    // Global help system functionality
    window.HelpSystem = {
        // Track opened help items for analytics
        trackHelpUsage: function(helpId, action = 'view') {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'help_system_usage', {
                    'help_id': helpId,
                    'action': action,
                    'page': window.location.pathname
                });
            }
        },

        // Show help tour for new users
        startTour: function(tourSteps) {
            // Implementation for guided tours
            console.log('Help tour started', tourSteps);
        },

        // Get contextual help based on current page/section
        getContextualHelp: function() {
            const path = window.location.pathname;
            const section = document.querySelector('[data-help-context]')?.dataset.helpContext;
            return {
                path: path,
                section: section,
                available_help: this.getAvailableHelp(path, section)
            };
        },

        getAvailableHelp: function(path, section) {
            // Return relevant help topics based on current context
            const helpTopics = [];
            
            if (path.includes('flashcard') || section === 'flashcards') {
                helpTopics.push({
                    title: 'Creating Flashcards',
                    url: '/docs/user/parent-guide.md#creating-flashcards'
                });
                helpTopics.push({
                    title: 'Card Types Guide',
                    url: '/docs/user/parent-guide.md#card-types'
                });
                helpTopics.push({
                    title: 'Import/Export Guide',
                    url: '/docs/guides/import-export.md'
                });
            }
            
            return helpTopics;
        }
    };

    // Track help tooltip interactions
    document.addEventListener('click', function(e) {
        const helpTrigger = e.target.closest('[data-help-trigger]');
        if (helpTrigger) {
            const helpId = helpTrigger.getAttribute('data-help-trigger');
            HelpSystem.trackHelpUsage(helpId, 'click');
        }
    });

    // Keyboard shortcuts for help
    document.addEventListener('keydown', function(e) {
        // F1 or Shift+? for general help
        if (e.key === 'F1' || (e.shiftKey && e.key === '?')) {
            e.preventDefault();
            const contextualHelp = HelpSystem.getContextualHelp();
            console.log('Contextual help requested', contextualHelp);
            // Show help modal or panel
        }
    });
</script>
@endonce