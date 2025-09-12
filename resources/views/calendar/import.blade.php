@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('import_calendar') }}</h1>
            
            @if($children->isEmpty())
                <div class="text-center py-8">
                    <div class="text-gray-500 mb-4">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2L3 9v9a2 2 0 002 2h4l4-4h4a2 2 0 002-2V9l-7-7z"/>
                        </svg>
                        <p class="text-lg">{{ __('no_children_found') }}</p>
                        <p class="text-sm">{{ __('add_child_first_import_events') }}</p>
                    </div>
                    <a href="{{ route('children.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        {{ __('manage_children') }}
                    </a>
                </div>
            @else
                <form action="{{ route('calendar.import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-6">
                        <label for="child_id" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('select_child') }}
                        </label>
                        <select name="child_id" id="child_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="">{{ __('choose_child_ellipsis') }}</option>
                            @foreach($children as $child)
                                <option value="{{ $child->id }}" {{ $child->id == $selectedChildId ? 'selected' : '' }}>
                                    {{ $child->name }} ({{ $child->grade }} {{ __('Grade') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="ics_file" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('upload_ics_file') }}
                        </label>
                        <input type="file" name="ics_file" id="ics_file" accept=".ics,.ical" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('supported_formats_colon', ['formats' => implode(', ', $supportedExtensions)]) }}
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('dashboard.parent') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            {{ __('back_to_dashboard') }}
                        </a>
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                            {{ __('preview_import') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection