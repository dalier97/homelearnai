@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('subjects.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex items-center space-x-3">
                <div class="w-6 h-6 rounded-full" style="background-color: {{ $subject->color }}"></div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $subject->name }}</h1>
                    <p class="text-gray-600 mt-1">{{ $units->count() }} units</p>
                </div>
            </div>
        </div>
        
        <div class="flex space-x-3">
            <button 
                type="button"
                hx-get="{{ route('units.create', $subject->id) }}"
                hx-target="#unit-modal"
                hx-swap="innerHTML"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg shadow-sm transition-colors">
                Add Unit
            </button>
            <button 
                type="button"
                hx-get="{{ route('subjects.edit', $subject->id) }}"
                hx-target="#subject-modal"
                hx-swap="innerHTML"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-lg shadow-sm transition-colors">
                Edit Subject
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Units List -->
    <div id="units-list">
        @include('units.partials.units-list', compact('units', 'subject'))
    </div>

    <!-- Modals -->
    <div id="unit-modal"></div>
    <div id="subject-modal"></div>
</div>
@endsection