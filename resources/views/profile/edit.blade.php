@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profile') }}
            </h2>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-6">
            <p>Profile page content</p>
            <p>User: {{ $user->name ?? 'No user' }}</p>
            <p>Email: {{ $user->email ?? 'No email' }}</p>
        </div>
    </div>
</div>
@endsection
