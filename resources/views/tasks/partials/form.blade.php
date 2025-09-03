<div x-data="{ open: true }" 
     x-show="open"
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <!-- Backdrop -->
        <div @click="open = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form hx-post="{{ isset($task->id) ? route('tasks.update', $task->id) : route('tasks.store') }}"
                  hx-target="{{ isset($task->id) ? '#task-' . $task->id : '#task-list' }}"
                  hx-swap="{{ isset($task->id) ? 'outerHTML' : 'afterbegin' }}"
                  @submit="open = false">
                @if(isset($task->id))
                    @method('PUT')
                @endif
                @csrf
                
                <div class="bg-white px-6 pt-5 pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ isset($task->id) ? 'Edit Task' : 'New Task' }}
                        </h3>
                        <button @click="open = false" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input 
                                type="text" 
                                name="title" 
                                id="title"
                                value="{{ old('title', $task->title ?? '') }}"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea 
                                name="description" 
                                id="description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $task->description ?? '') }}</textarea>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                            <select 
                                name="priority" 
                                id="priority"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="low" {{ (old('priority', $task->priority ?? '') == 'low') ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ (old('priority', $task->priority ?? 'medium') == 'medium') ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ (old('priority', $task->priority ?? '') == 'high') ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ (old('priority', $task->priority ?? '') == 'urgent') ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input 
                                type="date" 
                                name="due_date" 
                                id="due_date"
                                value="{{ old('due_date', isset($task->due_date) ? $task->due_date->format('Y-m-d') : '') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                    <button 
                        @click="open = false"
                        type="button" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        {{ isset($task->id) ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>