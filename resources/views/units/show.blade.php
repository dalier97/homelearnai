@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('subjects.show', $subject->id) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center space-x-3 mb-1">
                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $subject->color }}"></div>
                    <span class="text-sm text-gray-600">{{ $subject->name }}</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $unit->name }}</h1>
                @if($unit->description)
                    <p class="text-gray-600 mt-1">{{ $unit->description }}</p>
                @endif
            </div>
        </div>
        
        <div class="flex space-x-3">
            <button 
                type="button"
                hx-get="{{ route('topics.create', [$subject->id, $unit->id]) }}"
                hx-target="#topic-modal"
                hx-swap="innerHTML"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg shadow-sm transition-colors">
                Add Topic
            </button>
            <button 
                type="button"
                hx-get="{{ route('units.edit', [$subject->id, $unit->id]) }}"
                hx-target="#unit-modal"
                hx-swap="innerHTML"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-lg shadow-sm transition-colors">
                Edit Unit
            </button>
        </div>
    </div>

    <!-- Unit Info -->
    @if($unit->target_completion_date)
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm text-gray-700">
                    Target completion: {{ $unit->target_completion_date->translatedFormat('M j, Y') }}
                    @if($unit->isOverdue())
                        <span class="ml-2 text-red-600 font-medium">(Overdue)</span>
                    @endif
                </span>
            </div>
        </div>
    @endif

    <!-- Topics List -->
    <div id="topics-list">
        @include('topics.partials.topics-list', compact('topics', 'unit', 'subject'))
    </div>

    <!-- Modals -->
    <div id="topic-modal"></div>
    <div id="unit-modal"></div>
</div>
@endsection