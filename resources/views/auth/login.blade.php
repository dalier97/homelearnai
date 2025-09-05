@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full space-y-8">
    <div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">{{ __('Sign in to Homeschool Hub') }}</h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ __('Or') }}
        <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500">
          {{ __('create a new account') }}
        </a>
      </p>
    </div>

    <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
      @csrf
      
      @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
        <p>{{ session('success') }}</p>
      </div>
      @endif
      
      @if($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
        @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
        @endforeach
      </div>
      @endif

      <div class="rounded-md shadow-sm -space-y-px">
        <div>
          <label for="email" class="sr-only">{{ __('Email address') }}</label>
          <input
            id="email"
            name="email"
            type="email"
            autocomplete="email"
            required
            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
            placeholder="{{ __('Email address') }}"
            value="{{ old('email') }}"
          />
        </div>
        <div>
          <label for="password" class="sr-only">{{ __('Password') }}</label>
          <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            required
            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
            placeholder="{{ __('Password') }}"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          {{ __('Sign in') }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
