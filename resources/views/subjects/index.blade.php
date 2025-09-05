@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('subjects') }}</h2>
                <p class="text-sm text-gray-600 mt-1">{{ __('manage_curriculum_subjects_organize_units') }}</p>
            </div>
            <button 
                hx-get="{{ route('subjects.create') }}"
                hx-target="#subject-modal"
                hx-swap="innerHTML"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('add_subject') }}
            </button>
        </div>
    </div>

    <!-- Subjects List -->
    <div class="p-6">
        <div id="subjects-list" hx-get="{{ route('subjects.index') }}" hx-trigger="load" hx-swap="innerHTML">
            @include('subjects.partials.subjects-list', ['subjects' => $subjects])
        </div>
    </div>
</div>

<!-- Subject Modal -->
<div id="subject-modal">
    <!-- {{ __('modal_content_loaded_by_htmx') }} -->
</div>

<!-- Quick Start Modal -->
<div id="quick-start-modal"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show modal when content is loaded
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.target.id === 'subject-modal' && event.detail.xhr.status === 200) {
            // Modal content is now loaded and visible
        }
    });
    
    // Hide modal and refresh list after successful form submission
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.target.closest('#subject-modal') && event.detail.xhr.status === 200 && event.detail.target.tagName === 'FORM') {
            // Clear modal content to hide it
            document.getElementById('subject-modal').innerHTML = '';
            document.getElementById('subject-modal').classList.add('hidden');
            
            // Refresh subjects list
            htmx.trigger('#subjects-list', 'refresh');
        }
    });
    
    // Show modal when HTMX loads content into it
    document.body.addEventListener('htmx:beforeSwap', function(event) {
        if (event.detail.target.id === 'subject-modal') {
            document.getElementById('subject-modal').classList.remove('hidden');
        }
    });
});
</script>
@endsection