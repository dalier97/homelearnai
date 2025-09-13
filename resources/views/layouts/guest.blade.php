<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <style>
            .rounded-md { border-radius: 0.375rem; }
            .border-gray-300 { border: 1px solid #d1d5db; }
            .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
            .text-red-600 { color: #dc2626; }
            .text-sm { font-size: 0.875rem; }
            .block { display: block; }
            .mt-1 { margin-top: 0.25rem; }
            .mt-4 { margin-top: 1rem; }
            .w-full { width: 100%; }
            .bg-indigo-600 { background-color: #4f46e5; }
            .text-white { color: white; }
            .px-4 { padding-left: 1rem; padding-right: 1rem; }
            .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
            .ml-3 { margin-left: 0.75rem; }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <svg class="w-20 h-20 fill-current text-gray-500" viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg">
                        <path d="M158 0C70.8 0 0 70.8 0 158s70.8 158 158 158 158-70.8 158-158S245.2 0 158 0zM158 237c-43.5 0-79-35.5-79-79s35.5-79 79-79 79 35.5 79 79-35.5 79-79 79z"/>
                    </svg>
                </a>
            </div>

            @yield('content')
        </div>
    </body>
</html>
