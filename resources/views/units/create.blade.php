@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ __('Create Unit') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('Add a new unit to') }} {{ $subject->name }}</p>
            </div>
            <div>
                <a href="{{ route('units.index', ['subject' => $subject->id]) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border">
            @include('units.partials.create-form', compact('subject'))
        </div>
    </div>
</div>
@endsection