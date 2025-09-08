<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full" style="background-color: {{ $subject->color }}"></div>
                <h2 class="text-2xl font-bold">{{ $subject->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('subjects.edit', ['subject' => $subject->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    {{ __('Edit Subject') }}
                </a>
                <form method="POST" action="{{ route('subjects.destroy', ['subject' => $subject->id]) }}" 
                      onsubmit="return confirm('{{ __('Are you sure you want to delete this subject?') }}')" 
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

        @if($subject->description)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Description') }}</h3>
                <p class="text-gray-600">{{ $subject->description }}</p>
            </div>
        @endif

        <div class="border-t pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('Units') }}</h3>
                <a href="{{ route('units.create', ['subject' => $subject->id]) }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    {{ __('Add Unit') }}
                </a>
            </div>

            @if($units->count() > 0)
                <div class="grid gap-4">
                    @foreach($units as $unit)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium">{{ $unit->title }}</h4>
                                    @if($unit->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $unit->description }}</p>
                                    @endif
                                    @if($unit->target_completion_date)
                                        <p class="text-xs text-blue-600 mt-2">
                                            {{ __('Target completion') }}: {{ $unit->target_completion_date->format('M j, Y') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('units.show', ['unit' => $unit->id]) }}" 
                                       class="text-blue-600 hover:text-blue-700 text-sm">
                                        {{ __('View') }}
                                    </a>
                                    <a href="{{ route('units.edit', ['unit' => $unit->id]) }}" 
                                       class="text-gray-600 hover:text-gray-700 text-sm">
                                        {{ __('Edit') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>{{ __('No units yet. Add your first unit to get started.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>