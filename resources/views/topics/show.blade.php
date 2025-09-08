@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ $topic->title }}</h1>
                <p class="text-gray-600 mt-2">{{ $subject->name }} → {{ $unit->title }} → {{ $topic->title }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('topics.index', ['unit' => $unit->id]) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Back to Topics') }}
                </a>
                <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Edit Topic') }}
                </a>
            </div>
        </div>

        @include('topics.partials.topic-details', compact('topic', 'unit', 'subject'))
    </div>
</div>
@endsection