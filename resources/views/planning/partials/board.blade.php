<!-- Kanban Planning Board -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6">
  <!-- Backlog Column -->
  <div class="kanban-column bg-gray-50 rounded-lg p-4" data-status="backlog">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 flex items-center">
        <div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
        {{ __('backlog') }}
        <span class="ml-2 bg-gray-200 text-gray-700 px-2 py-1 text-xs rounded-full">
          {{ $sessionsByStatus['backlog']->count() }}
        </span>
      </h3>
    </div>
    
    <div class="space-y-3 min-h-12">
      @foreach($sessionsByStatus['backlog'] as $session)
        @include('planning.partials.session-card', ['session' => $session])
      @endforeach
    </div>
    
    <!-- Add Session Button -->
    @if($availableTopics->count() > 0)
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

  <!-- Planned Column -->
  <div class="kanban-column bg-blue-50 rounded-lg p-4" data-status="planned">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 flex items-center">
        <div class="w-3 h-3 bg-blue-400 rounded-full mr-2"></div>
        {{ __('planned') }}
        <span class="ml-2 bg-blue-200 text-blue-700 px-2 py-1 text-xs rounded-full">
          {{ $sessionsByStatus['planned']->count() }}
        </span>
      </h3>
    </div>
    
    <div class="space-y-3 min-h-12">
      @foreach($sessionsByStatus['planned'] as $session)
        @include('planning.partials.session-card', ['session' => $session])
      @endforeach
    </div>
    
    @if($sessionsByStatus['planned']->isEmpty())
    <div class="text-center py-8 text-gray-400">
      <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
      <p class="text-sm">{{ __('drag_sessions_here_to_plan_them') }}</p>
    </div>
    @endif
  </div>

  <!-- Scheduled Column -->
  <div class="kanban-column scheduled bg-green-50 rounded-lg p-4" data-status="scheduled">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 flex items-center">
        <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
        {{ __('scheduled') }}
        <span class="ml-2 bg-green-200 text-green-700 px-2 py-1 text-xs rounded-full">
          {{ $sessionsByStatus['scheduled']->count() }}
        </span>
      </h3>
    </div>
    
    <div class="space-y-3 min-h-12">
      @foreach($sessionsByStatus['scheduled'] as $session)
        @include('planning.partials.session-card', ['session' => $session])
      @endforeach
    </div>
    
    @if($sessionsByStatus['scheduled']->isEmpty())
    <div class="text-center py-8 text-gray-400">
      <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <p class="text-sm">{{ __('drag_planned_sessions_here_to_schedule_them') }}</p>
    </div>
    @endif
  </div>

  <!-- Done Column -->
  <div class="kanban-column bg-purple-50 rounded-lg p-4" data-status="done">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 flex items-center">
        <div class="w-3 h-3 bg-purple-400 rounded-full mr-2"></div>
        {{ __('done') }}
        <span class="ml-2 bg-purple-200 text-purple-700 px-2 py-1 text-xs rounded-full">
          {{ $sessionsByStatus['done']->count() }}
        </span>
      </h3>
    </div>
    
    <div class="space-y-3 min-h-12">
      @foreach($sessionsByStatus['done'] as $session)
        @include('planning.partials.session-card', ['session' => $session])
      @endforeach
    </div>
    
    @if($sessionsByStatus['done']->isEmpty())
    <div class="text-center py-8 text-gray-400">
      <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-sm">{{ __('completed_sessions_appear_here') }}</p>
    </div>
    @endif
  </div>

  <!-- Catch-Up Lane Column -->
  @include('planning.partials.catch-up-column', ['catchUpSessions' => $catchUpSessions, 'selectedChild' => $selectedChild])
</div>