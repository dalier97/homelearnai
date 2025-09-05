<!-- Individual Kanban Column -->
<div class="kanban-column bg-{{ $status === 'backlog' ? 'gray' : ($status === 'planned' ? 'blue' : ($status === 'scheduled' ? 'green' : 'purple')) }}-50 rounded-lg p-4" data-status="{{ $status }}">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold text-gray-900 flex items-center">
      <div class="w-3 h-3 bg-{{ $status === 'backlog' ? 'gray' : ($status === 'planned' ? 'blue' : ($status === 'scheduled' ? 'green' : 'purple')) }}-400 rounded-full mr-2"></div>
      {{ $statusTitle }}
      <span class="ml-2 bg-{{ $status === 'backlog' ? 'gray' : ($status === 'planned' ? 'blue' : ($status === 'scheduled' ? 'green' : 'purple')) }}-200 text-{{ $status === 'backlog' ? 'gray' : ($status === 'planned' ? 'blue' : ($status === 'scheduled' ? 'green' : 'purple')) }}-700 px-2 py-1 text-xs rounded-full">
        {{ $sessions->count() }}
      </span>
    </h3>
  </div>
  
  <div class="space-y-3 min-h-12">
    @foreach($sessions as $session)
      @include('planning.partials.session-card', ['session' => $session])
    @endforeach
  </div>
  
  @if($sessions->isEmpty())
    <div class="text-center py-8 text-gray-400">
      <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        @if($status === 'planned')
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        @elseif($status === 'scheduled')
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        @elseif($status === 'done')
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        @endif
      </svg>
      <p class="text-sm">
        @if($status === 'planned')
          {{ __('drag_sessions_here_to_plan') }}
        @elseif($status === 'scheduled')
          {{ __('drag_planned_sessions_here_to_schedule') }}  
        @elseif($status === 'done')
          {{ __('completed_sessions_appear_here') }}
        @else
          {{ __('no_sessions_yet') }}
        @endif
      </p>
    </div>
  @endif
  
  @if($status === 'backlog')
    <!-- Add Session Button -->
    <div class="mt-4 pt-4 border-t border-gray-200">
      <button
        hx-get="{{ route('planning.create-session') }}?child_id={{ $selectedChild->id }}"
        hx-target="#modal-container"
        hx-swap="innerHTML"
        class="w-full py-2 px-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-gray-400 hover:text-gray-600 text-sm font-medium transition-colors"
      >
        + {{ __('add_session_from_topic') }}
      </button>
    </div>
  @endif
</div>