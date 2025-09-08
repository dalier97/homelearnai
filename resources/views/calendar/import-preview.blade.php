@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ __('ICS Import Preview') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('Preview events from') }}: {{ $fileName }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('calendar.import', ['child' => $child->id]) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Events to Import') }}</h2>
                
                @if(count($preview['events']) === 0)
                    <p class="text-gray-500 text-center py-8">{{ __('No events found in the ICS file.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach($preview['events'] as $event)
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <h3 class="font-medium">{{ $event['title'] }}</h3>
                                <p class="text-sm text-gray-600">
                                    {{ date('M j, Y g:i A', strtotime($event['start'])) }} - 
                                    {{ date('g:i A', strtotime($event['end'])) }}
                                </p>
                                @if(!empty($event['description']))
                                    <p class="text-sm text-gray-500 mt-1">{{ $event['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('calendar.import.process', ['child' => $child->id]) }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="preview_data" value="{{ base64_encode(json_encode($preview)) }}">
                        <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                            {{ __('Import') }} {{ count($preview['events']) }} {{ __('Events') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection