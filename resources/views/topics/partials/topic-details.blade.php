<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ $topic->title }}</h2>
                <p class="text-gray-600">{{ $subject->name }} → {{ $unit->title }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    {{ __('Edit Topic') }}
                </a>
                <form method="POST" action="{{ route('topics.destroy', ['topic' => $topic->id]) }}" 
                      onsubmit="return confirm('{{ __('Are you sure you want to delete this topic?') }}')" 
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        {{ __('Delete') }}
                    </button>
                </form>
            </div>
        </div>

        @if($topic->description)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Description') }}</h3>
                <p class="text-gray-600">{{ $topic->description }}</p>
            </div>
        @endif

        @if($topic->learning_objectives)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Learning Objectives') }}</h3>
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-blue-800">{{ $topic->learning_objectives }}</p>
                </div>
            </div>
        @endif

        @if($topic->estimated_duration_minutes)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Estimated Duration') }}</h3>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-600">{{ $topic->estimated_duration_minutes }} {{ __('minutes') }}</span>
                </div>
            </div>
        @endif

        <div class="border-t pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('Learning Sessions') }}</h3>
                <a href="{{ route('planning.index') }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    {{ __('Schedule Sessions') }}
                </a>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">
                    {{ __('Use the Planning Board to schedule learning sessions for this topic.') }}
                    {{ __('Sessions can be adapted to different age levels and learning styles.') }}
                </p>
            </div>
        </div>

        @if($topic->prerequisites)
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Prerequisites') }}</h3>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-yellow-800">{{ $topic->prerequisites }}</p>
                </div>
            </div>
        @endif
    </div>
</div>