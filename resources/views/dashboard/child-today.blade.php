@extends('layouts.app')

@section('content')
@php
    $kidsMode = session('kids_mode_active', false);
    $motivationalMessages = [
        __('You\'re doing amazing! Keep it up! 🌟'),
        __('Learning is fun when you\'re awesome! 🎉'),
        __('You\'re a learning superstar! ⭐'),
        __('Great job today, :name!', ['name' => $child->name]) . ' 🚀',
        __('You\'ve got this! Keep learning! 💪'),
    ];
    $todayMessage = $motivationalMessages[array_rand($motivationalMessages)];
@endphp

<div class="max-w-4xl mx-auto space-y-6" 
     @if($kidsMode) 
     style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); min-height: 100vh; margin: -1.5rem; padding: 2rem; border-radius: 0;"
     @endif>
    
    <!-- Kids Mode Fun Header -->
    @if($kidsMode)
    <div class="text-center mb-8 animate-bounce">
        <div class="text-6xl mb-4">🎮🎯🌈</div>
        <h1 class="text-4xl font-bold bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 bg-clip-text text-transparent mb-2">
            {{ __('Hello, :name!', ['name' => $child->name]) }}
        </h1>
        <p class="text-2xl font-semibold text-purple-800">{{ $todayMessage }}</p>
    </div>
    @endif

    <!-- Header - Child-friendly -->
    <div class="@if($kidsMode) bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 shadow-2xl border-4 border-white @else bg-gradient-to-r from-blue-500 to-purple-600 @endif rounded-3xl p-8 text-white transform @if($kidsMode) hover:scale-105 transition-transform duration-300 @endif">
        <div class="flex items-center justify-between">
            <div>
                @if($kidsMode)
                    <h2 class="text-4xl font-bold mb-2 drop-shadow-lg">{{ __('Today\'s Adventures!') }} 🚀</h2>
                    <p class="text-xl font-semibold opacity-90">{{ date('l, F j, Y') }}</p>
                    <p class="text-lg mt-2 font-medium">{{ __('Ready to learn something awesome?') }} ✨</p>
                @else
                    <h2 class="text-2xl font-bold">{{ __(':name\'s Learning Today', ['name' => $child->name]) }} 🌟</h2>
                    <p class="text-blue-100 mt-1">{{ date('l, F j, Y') }} - {{ __('Let\'s make today amazing!') }}</p>
                @endif
            </div>
            <div class="text-right">
                @if($kidsMode)
                    <div class="bg-white/20 rounded-full p-6 backdrop-blur-sm">
                        <div class="text-5xl font-bold drop-shadow-lg">{{ $today_sessions->count() }}</div>
                        <div class="text-lg font-semibold">{{ __('Adventures') }}</div>
                    </div>
                @else
                    <div class="text-3xl font-bold">{{ $today_sessions->count() }}</div>
                    <div class="text-sm text-blue-100">{{ trans_choice('session today|sessions today', $today_sessions->count()) }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if($kidsMode)
            <!-- Kids Mode Stats - Fun and Colorful -->
            <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl p-6 text-white shadow-xl transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
                <div class="text-center">
                    <div class="text-4xl mb-3">🎯</div>
                    <p class="text-lg font-bold mb-1">{{ __('Adventures Today') }}</p>
                    <p class="text-4xl font-bold">{{ $today_sessions->count() }}</p>
                    <p class="text-sm opacity-90 mt-2">{{ __('Let\'s conquer them all!') }}</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl p-6 text-white shadow-xl transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
                <div class="text-center">
                    <div class="text-4xl mb-3">⭐</div>
                    <p class="text-lg font-bold mb-1">{{ __('Review Stars') }}</p>
                    <p class="text-4xl font-bold">{{ $review_queue->count() }}</p>
                    <p class="text-sm opacity-90 mt-2">{{ __('Shine bright!') }}</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl p-6 text-white shadow-xl transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
                <div class="text-center">
                    <div class="text-4xl mb-3">🚀</div>
                    <p class="text-lg font-bold mb-1">{{ __('Power Level') }}</p>
                    <p class="text-4xl font-bold">{{ $child->independence_level }}</p>
                    <p class="text-sm opacity-90 mt-2">{{ __('Super learner!') }}</p>
                </div>
            </div>
        @else
            <!-- Regular Mode Stats -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-600">{{ __('Today\'s Sessions') }}</p>
                        <p class="text-2xl font-bold text-green-900">{{ $today_sessions->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">{{ __('Review Queue') }}</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $review_queue->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">{{ __('Independence') }}</p>
                        <p class="text-lg font-bold text-purple-900">{{ __('Level :level', ['level' => $child->independence_level]) }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Today's Learning Sessions -->
    <div class="@if($kidsMode) bg-gradient-to-r from-yellow-200 to-pink-200 border-4 border-purple-300 @else bg-white @endif rounded-2xl shadow-lg">
        <div class="px-6 py-4 @if(!$kidsMode) border-b border-gray-200 @endif">
            <div class="flex items-center justify-between">
                @if($kidsMode)
                    <h3 class="text-2xl font-bold text-purple-800 flex items-center">
                        <div class="text-3xl mr-3">🎪</div>
                        {{ __('Learning Adventures') }}
                    </h3>
                    @if($can_reorder)
                        <span class="text-lg bg-gradient-to-r from-blue-400 to-blue-600 text-white px-4 py-2 rounded-full font-bold shadow-lg animate-pulse">{{ __('You can move these around!') }} 🎮</span>
                    @endif
                @else
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('Today\'s Sessions') }}
                    </h3>
                    @if($can_reorder)
                        <span class="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ __('You can reorder these!') }}</span>
                    @endif
                @endif
            </div>
        </div>
        <div id="today-sessions" class="p-6">
            @if($today_sessions->count() > 0)
                <div class="@if($kidsMode) space-y-6 @else space-y-4 @endif" @if($can_reorder) x-data="{ reorderEnabled: true }" @endif>
                    @foreach($today_sessions as $index => $session)
                        @php
                            $sessionColors = ['from-red-300 to-red-500', 'from-blue-300 to-blue-500', 'from-green-300 to-green-500', 'from-purple-300 to-purple-500', 'from-pink-300 to-pink-500'];
                            $sessionColor = $sessionColors[$index % count($sessionColors)];
                        @endphp
                        
                        <div class="@if($kidsMode) bg-gradient-to-r {{ $sessionColor }} border-3 border-white shadow-xl rounded-2xl p-6 transform hover:scale-105 hover:rotate-1 @else bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-sm @endif transition-all duration-300 session-card"
                             @if($can_reorder) draggable="true" data-session-id="{{ $session->id }}" @endif>
                            
                            @if($kidsMode)
                                <!-- Kids Mode Session Card -->
                                <div class="text-white">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            @if($can_reorder)
                                                <div class="text-2xl mr-3 cursor-move">🎯</div>
                                            @endif
                                            <div>
                                                <h4 class="text-2xl font-bold drop-shadow-lg">
                                                    {{ $session->topic->title ?? __('Learning Quest #:id', ['id' => $session->id]) }}
                                                </h4>
                                                <div class="flex items-center text-lg font-semibold opacity-90 mt-1">
                                                    <span class="mr-2">⏰</span>
                                                    <span>{{ __(':minutes mins', ['minutes' => $session->estimated_minutes]) }}</span>
                                                    @if($session->scheduled_start_time)
                                                        <span class="ml-4 mr-2">🕒</span>
                                                        <span>{{ Carbon\Carbon::parse($session->scheduled_start_time)->translatedFormat('g:i A') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        @if($session->status === 'completed')
                                            <div class="bg-white/20 rounded-2xl p-4 backdrop-blur-sm">
                                                <div class="text-4xl mb-2">🎉</div>
                                                <span class="text-2xl font-bold">{{ __('AWESOME!') }}</span>
                                                <p class="text-lg mt-2 font-semibold">{{ __('You completed this quest!') }}</p>
                                            </div>
                                        @else
                                            <button hx-post="{{ route('dashboard.sessions.complete', $session->id) }}" 
                                                    hx-target="closest .session-card"
                                                    hx-swap="outerHTML"
                                                    class="bg-yellow-400 hover:bg-yellow-500 text-purple-800 px-8 py-4 rounded-full text-2xl font-bold shadow-2xl transform hover:scale-110 transition-all duration-300 border-4 border-white">
                                                <div class="flex items-center space-x-3">
                                                    <span class="text-3xl">✅</span>
                                                    <span>{{ __('Complete Quest!') }}</span>
                                                </div>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- Regular Mode Session Card -->
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            @if($can_reorder)
                                                <svg class="w-5 h-5 text-gray-400 cursor-move" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                                </svg>
                                            @endif
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900">
                                                    {{ $session->topic->title ?? __('Learning Session #:id', ['id' => $session->id]) }}
                                                </h4>
                                                <p class="text-sm text-gray-600">
                                                    {{ __('Estimated time: :minutes minutes', ['minutes' => $session->estimated_minutes]) }}
                                                    @if($session->scheduled_start_time)
                                                        • {{ __('Scheduled for :time', ['time' => Carbon\Carbon::parse($session->scheduled_start_time)->translatedFormat('g:i A')]) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        @if($session->status === 'completed')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ __('Complete!') }}
                                            </span>
                                        @else
                                            <button hx-post="{{ route('dashboard.sessions.complete', $session->id) }}" 
                                                    hx-target="closest .session-card"
                                                    hx-swap="outerHTML"
                                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>{{ __('Complete') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Evidence Capture (shown after completion) -->
                            @if($session->status === 'completed' && !$session->hasEvidence())
                                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                                    <h5 class="text-sm font-medium text-blue-900 mb-2">📸 {{ __('Show what you learned!') }}</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Photo') }}</span>
                                        </button>
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Voice Note') }}</span>
                                        </button>
                                        <button class="text-center p-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                            <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span class="text-sm text-blue-700">{{ __('Write Notes') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                @if($kidsMode)
                    <!-- Kids Mode - No Sessions -->
                    <div class="text-center py-12">
                        <div class="text-8xl mb-6 animate-bounce">🎈</div>
                        <h3 class="text-3xl font-bold text-purple-800 mb-4">{{ __('No Adventures Today!') }}</h3>
                        <p class="text-xl font-semibold text-pink-700">{{ __('Time to play and have fun!') }} 🎉✨</p>
                        <div class="mt-6 flex justify-center space-x-4">
                            <div class="text-4xl">🎮</div>
                            <div class="text-4xl">📚</div>
                            <div class="text-4xl">🎨</div>
                            <div class="text-4xl">🌟</div>
                        </div>
                    </div>
                @else
                    <!-- Regular Mode - No Sessions -->
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No sessions scheduled') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Enjoy your free time!') }} 🎉</p>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Review Queue -->
    @if($review_queue->count() > 0)
        <div class="@if($kidsMode) bg-gradient-to-r from-indigo-300 to-purple-400 border-4 border-yellow-300 @else bg-white @endif rounded-2xl shadow-lg">
            @if($kidsMode)
                <!-- Kids Mode Review Section -->
                <div class="px-8 py-6 text-center">
                    <div class="text-6xl mb-4">⭐🧠⭐</div>
                    <h3 class="text-3xl font-bold text-white mb-3 drop-shadow-lg">
                        {{ trans_choice('Time to Shine! (:count Star)|Time to Shine! (:count Stars)', $review_queue->count(), ['count' => $review_queue->count()]) }}
                    </h3>
                    <p class="text-xl font-semibold text-yellow-100 mb-6">{{ __('Show off what you remember!') }}</p>
                    <a href="{{ route('reviews.session', $child->id) }}" 
                       class="inline-flex items-center bg-gradient-to-r from-yellow-400 to-orange-500 text-purple-800 px-10 py-5 rounded-full text-2xl font-bold shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 border-4 border-white">
                        <span class="text-3xl mr-3">🚀</span>
                        {{ __('Start Star Quest!') }}
                    </a>
                </div>
            @else
                <!-- Regular Mode Review Section -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        {{ trans_choice('Quick Review (:count item)|Quick Review (:count items)', $review_queue->count(), ['count' => $review_queue->count()]) }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <p class="text-gray-600 mb-4">{{ __('Let\'s review some things you\'ve learned before!') }}</p>
                        <a href="{{ route('reviews.session', $child->id) }}" 
                           class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            {{ __('Start Review Session') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- This Week View (for Level 3+ independence) - Hidden in Kids Mode -->
    @if($can_move_weekly && !empty($week_sessions) && !$kidsMode)
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ __('This Week\'s Plan') }}
                    <span class="ml-2 text-sm bg-purple-100 text-purple-700 px-2 py-1 rounded">{{ __('You can move sessions!') }}</span>
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-7 gap-4">
                    @foreach($week_sessions as $dayOfWeek => $dayData)
                        <div class="text-center">
                            <h4 class="font-medium text-gray-900 mb-2">{{ substr($dayData['day_name'], 0, 3) }}</h4>
                            <div class="text-sm text-gray-500 mb-3">{{ $dayData['date']->translatedFormat('M j') }}</div>
                            <div class="space-y-2">
                                @foreach($dayData['sessions']->take(3) as $session)
                                    <div class="bg-gray-50 p-2 rounded text-xs border session-movable" 
                                         draggable="true" data-session-id="{{ $session->id }}">
                                        {{ $session->topic->title ?? __('Session') }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Celebration Section -->
    @if($kidsMode)
        <!-- Kids Mode Celebration -->
        <div class="bg-gradient-to-r from-pink-400 via-purple-500 to-indigo-600 rounded-3xl shadow-2xl p-10 text-white text-center border-4 border-yellow-300">
            <div class="text-8xl mb-6 animate-bounce">🎉</div>
            <h3 class="text-4xl font-bold mb-4 drop-shadow-lg">{{ __('You\'re AMAZING, :name!', ['name' => $child->name]) }}</h3>
            <p class="text-2xl font-semibold mb-6">{{ __('Keep being a learning superstar!') }}</p>
            <div class="flex justify-center space-x-4 text-5xl animate-pulse">
                <span>🌟</span>
                <span>⭐</span>
                <span>✨</span>
                <span>💫</span>
                <span>🎯</span>
            </div>
        </div>
    @else
        <!-- Regular Mode Celebration -->
        <div class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg shadow-lg p-6 text-white text-center">
            <h3 class="text-lg font-bold mb-2">🎉 {{ __('You\'re doing great!') }} 🎉</h3>
            <p class="text-yellow-100">{{ __('Keep up the awesome learning, :name!', ['name' => $child->name]) }}</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Kids Mode functionality
    const kidsMode = {{ $kidsMode ? 'true' : 'false' }};
    
    // Confetti animation function for kids mode
    function createConfetti() {
        if (!kidsMode) return;
        
        const confettiCount = 50;
        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500'];
        
        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.top = '-10px';
            confetti.style.left = Math.random() * window.innerWidth + 'px';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.borderRadius = '50%';
            confetti.style.zIndex = '9999';
            confetti.style.pointerEvents = 'none';
            confetti.style.animation = `fall ${2 + Math.random() * 3}s linear forwards`;
            
            document.body.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }
    }
    
    // Add confetti CSS animation
    if (kidsMode) {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
                100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
            }
            
            @keyframes celebrate {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
            
            .celebrate-animation {
                animation: celebrate 0.6s ease-in-out;
            }
        `;
        document.head.appendChild(style);
    }

    // Handle session completion with kids mode enhancements
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.xhr.status === 200 && event.detail.elt.textContent.includes('Complete')) {
            if (kidsMode) {
                // Kids mode celebration
                createConfetti();
                showToast('🎉 {{ __('AWESOME JOB!') }} 🌟', 'success');
                
                // Play celebration sound effect (if available)
                playSuccessSound();
                
                // Add celebration animation to completed session
                const completedCard = event.detail.elt.closest('.session-card');
                if (completedCard) {
                    completedCard.classList.add('celebrate-animation');
                }
            } else {
                showToast('{{ __('Great job! Session completed!') }} 🌟', 'success');
            }
        }
    });
    
    // Fun success sound for kids mode
    function playSuccessSound() {
        if (!kidsMode) return;
        
        // Create a simple success sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(523, audioContext.currentTime); // C5
            oscillator.frequency.setValueAtTime(659, audioContext.currentTime + 0.1); // E5
            oscillator.frequency.setValueAtTime(784, audioContext.currentTime + 0.2); // G5
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            // Silently fail if Web Audio API is not supported
        }
    }

    // Drag and drop for reordering (if allowed)
    @if($can_reorder)
    document.addEventListener('DOMContentLoaded', function() {
        const sessionCards = document.querySelectorAll('.session-card');
        
        sessionCards.forEach(card => {
            card.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.sessionId);
                this.style.opacity = '0.5';
            });

            card.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
            });

            card.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#f0f9ff';
            });

            card.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });

            card.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const draggedId = e.dataTransfer.getData('text/plain');
                const targetId = this.dataset.sessionId;
                
                if (draggedId !== targetId) {
                    // Here you would implement the reorder logic
                    showToast('{{ __('Sessions reordered!') }} 📝', 'success');
                }
            });
        });
    });
    @endif

    // Weekly session moving (if allowed)
    @if($can_move_weekly)
    document.addEventListener('DOMContentLoaded', function() {
        const movableSessions = document.querySelectorAll('.session-movable');
        
        movableSessions.forEach(session => {
            session.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.sessionId);
            });
        });

        // Add drop zones for each day
        document.querySelectorAll('.grid.grid-cols-7 > div').forEach(dayColumn => {
            dayColumn.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#f3e8ff';
            });

            dayColumn.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });

            dayColumn.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const sessionId = e.dataTransfer.getData('text/plain');
                // Here you would implement the move session logic
                showToast('{{ __('Session moved to different day!') }} 📅', 'success');
            });
        });
    });
    @endif

    // === KIDS MODE SECURITY PROTECTIONS ===
    @if(session('kids_mode_active'))
    
    // 1. Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // 2. Disable developer tools shortcuts
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+I (Chrome DevTools)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+J (Chrome Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            return false;
        }
        // Ctrl+Shift+C (Chrome Inspect)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            return false;
        }
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            return false;
        }
    });

    // 3. Disable text selection on UI elements
    const uiElements = document.querySelectorAll('.bg-white, .shadow, button, .card');
    uiElements.forEach(element => {
        element.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });
    });

    // 4. Detect developer tools opening
    let devtools = { open: false };
    const threshold = 160;
    
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                // Redirect when dev tools detected
                window.location.href = '{{ route("kids-mode.exit") }}';
            }
        } else {
            devtools.open = false;
        }
    }, 500);

    // 5. Session timeout protection (auto-redirect after 1 hour of inactivity)
    let lastActivity = Date.now();
    const SESSION_TIMEOUT = 60 * 60 * 1000; // 1 hour
    
    const activityEvents = ['click', 'keypress', 'scroll', 'touchstart'];
    activityEvents.forEach(event => {
        document.addEventListener(event, () => lastActivity = Date.now());
    });
    
    setInterval(function() {
        const inactiveTime = Date.now() - lastActivity;
        if (inactiveTime > SESSION_TIMEOUT) {
            window.location.href = '{{ route("dashboard") }}';
        }
    }, 60000); // Check every minute

    // 6. Monitor for unauthorized navigation attempts
    window.addEventListener('beforeunload', function(e) {
        // Allow normal HTMX navigation but block external navigation
        if (!e.target.activeElement?.hasAttribute('hx-post') && 
            !e.target.activeElement?.hasAttribute('hx-get')) {
            e.preventDefault();
            e.returnValue = '{{ __("Are you sure you want to leave?") }}';
            return e.returnValue;
        }
    });

    @endif
</script>
@endpush