<div id="task-{{ $task->id }}" 
     class="bg-white rounded-lg shadow-sm p-4 priority-{{ $task->priority }} hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 flex-1">
            <!-- Checkbox -->
            <input 
                type="checkbox"
                {{ $task->status === 'completed' ? 'checked' : '' }}
                hx-post="{{ route('tasks.toggle', $task->id) }}"
                hx-target="#task-{{ $task->id }}"
                hx-swap="outerHTML"
                class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
            
            <!-- Task Info -->
            <div class="flex-1 {{ $task->status === 'completed' ? 'line-through opacity-60' : '' }}">
                <h3 class="font-semibold text-gray-900">{{ $task->title }}</h3>
                @if($task->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $task->description }}</p>
                @endif
                
                <div class="flex items-center space-x-4 mt-2 text-sm">
                    <!-- Priority Badge -->
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        @if($task->priority === 'urgent') bg-red-100 text-red-800
                        @elseif($task->priority === 'high') bg-orange-100 text-orange-800
                        @elseif($task->priority === 'medium') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($task->priority) }}
                    </span>
                    
                    <!-- Due Date -->
                    @if($task->due_date)
                        <span class="text-gray-500 {{ $task->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                            Due: {{ $task->due_date->format('M d, Y') }}
                        </span>
                    @endif
                    
                    <!-- Status -->
                    <span class="text-gray-500">
                        {{ $task->status === 'completed' ? 'Completed' : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center space-x-2">
            <button 
                hx-get="{{ route('tasks.edit', $task->id) }}"
                hx-target="#task-form-modal"
                hx-swap="innerHTML"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </button>
            
            <button 
                hx-delete="{{ route('tasks.destroy', $task->id) }}"
                hx-target="#task-{{ $task->id }}"
                hx-swap="outerHTML swap:200ms"
                hx-confirm="Are you sure you want to delete this task?"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</div>