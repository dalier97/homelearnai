@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ $subject->name }} {{ __('Units') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('Manage units for') }} {{ $subject->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('subjects.show', ['subject' => $subject->id]) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Back to Subject') }}
                </a>
                <a href="{{ route('units.create', ['subject' => $subject->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Add Unit') }}
                </a>
            </div>
        </div>

        @include('units.partials.units-list', compact('units', 'subject'))
    </div>
</div>
@endsection