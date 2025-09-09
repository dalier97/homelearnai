@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('children.index') }}" 
                   class="text-gray-600 hover:text-gray-800 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                
                <!-- Child Avatar & Info -->
                <div class="w-16 h-16 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-xl">
                    {{ substr($child->name, 0, 1) }}
                </div>
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $child->name }}</h1>
                    <div class="flex items-center space-x-4 text-gray-600">
                        <span>{{ __('years_old', ['age' => $child->age]) }}</span>
                        <span>•</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $child->getAgeGroup())) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('calendar.index', ['child_id' => $child->id]) }}" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ __('view_full_calendar') }}</span>
                </a>
                
                <button
                    hx-get="{{ route('children.edit', $child->id) }}"
                    hx-target="#child-form-modal"
                    hx-swap="innerHTML"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    {{ __('edit_profile') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule Overview -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('weekly_schedule') }}</h2>
        
        @if(collect($timeBlocksByDay)->flatten()->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                @foreach([__('monday'), __('tuesday'), __('wednesday'), __('thursday'), __('friday'), __('saturday'), __('sunday')] as $index => $dayName)
                    @php $dayNumber = $index + 1; @endphp
                    <div class="border rounded-lg p-3">
                        <h3 class="font-medium text-gray-900 text-center mb-3">{{ $dayName }}</h3>
                        
                        @if($timeBlocksByDay[$dayNumber]->count() > 0)
                            <div class="space-y-2">
                                @foreach($timeBlocksByDay[$dayNumber] as $timeBlock)
                                    <div class="bg-blue-50 rounded p-2 text-sm">
                                        <div class="font-medium text-blue-900">{{ $timeBlock->label }}</div>
                                        <div class="text-blue-600">{{ $timeBlock->getTimeRange() }}</div>
                                        <div class="text-blue-500">{{ $timeBlock->getFormattedDuration() }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-gray-400 text-sm py-4">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                {{ __('no_schedule') }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500 text-lg">{{ __('no_schedule_created_yet') }}</p>
                <p class="text-gray-400 text-sm mt-2">{{ __('create_time_blocks_to_organize_childs_learning_schedule', ['name' => $child->name]) }}</p>
                <a href="{{ route('calendar.index', ['child_id' => $child->id]) }}" 
                   class="mt-4 inline-flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>{{ __('create_schedule') }}</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Curriculum Management -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('curriculum_subjects') }}</h2>
            <button
                hx-get="{{ route('subjects.create', ['child_id' => $child->id]) }}"
                hx-target="#subject-modal"
                hx-swap="innerHTML"
                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>{{ __('add_subject') }}</span>
            </button>
        </div>
        
        <div id="subjects-list">
            @if($subjects->count() > 0)
                @include('subjects.partials.subjects-list', ['subjects' => $subjects, 'showQuickStart' => false, 'selectedChild' => $child])
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-gray-500 text-lg">{{ __('no_subjects_created_yet') }}</p>
                    <p class="text-gray-400 text-sm mt-2">{{ __('start_building_curriculum_by_adding_subjects', ['name' => $child->name]) }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">{{ __('time_blocks') }}</p>
                    <p class="text-2xl font-bold text-blue-900">{{ collect($timeBlocksByDay)->flatten()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">{{ __('weekly_hours') }}</p>
                    <p class="text-2xl font-bold text-green-900">
                        @php
                            $totalMinutes = collect($timeBlocksByDay)->flatten()->sum(function($block) {
                                return $block->getDurationMinutes();
                            });
                            $hours = round($totalMinutes / 60, 1);
                        @endphp
                        {{ $hours }}h
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">{{ __('subjects') }}</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $subjects->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-orange-600">{{ __('progress') }}</p>
                    <p class="text-2xl font-bold text-orange-900">0%</p>
                    <p class="text-xs text-gray-500">{{ __('coming_soon') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Child Form -->
<div id="child-form-modal"></div>

<!-- Modal for Subject Form -->
<div id="subject-modal"></div>

@endsection