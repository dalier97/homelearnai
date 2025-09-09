@forelse($children as $child)
    @include('children.partials.item', ['child' => $child])
@empty
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <p class="text-gray-500 text-lg">{{ __('no_children_added_yet') }}</p>
        <p class="text-gray-400 text-sm mt-2">{{ __('add_first_child_start_planning') }}</p>
        <button
            hx-get="{{ route('children.create') }}"
            hx-target="#child-form-modal"
            hx-swap="innerHTML"
            class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
            data-testid="empty-state-add-child-btn"
        >
            {{ __('add_child') }}
        </button>
    </div>
@endforelse