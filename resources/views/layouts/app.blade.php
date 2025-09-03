<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'TaskMaster') }}</title>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
      .htmx-indicator {
        display: none;
      }
      .htmx-request .htmx-indicator {
        display: inline-block;
      }
      .htmx-swapping {
        opacity: 0;
        transition: opacity 200ms ease-out;
      }

      .priority-urgent {
        border-left: 4px solid #ef4444;
      }
      .priority-high {
        border-left: 4px solid #f59e0b;
      }
      .priority-medium {
        border-left: 4px solid #3b82f6;
      }
      .priority-low {
        border-left: 4px solid #6b7280;
      }
    </style>

    @stack('styles')
  </head>
  <body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900">
              <a href="{{ route('dashboard') }}">TaskMaster</a>
            </h1>
          </div>

          @auth
          <div class="flex items-center space-x-4">
            <span class="text-gray-700"> Welcome, {{ session('user')['email'] ?? 'User' }} </span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
              @csrf
              <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
            </form>
          </div>
          @endauth
        </div>
      </div>
    </nav>

    <!-- Loading Indicator -->
    <div class="htmx-indicator fixed top-4 right-4 z-50">
      <div class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center">
        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        Loading...
      </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">@yield('content')</main>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- HTMX Response Headers -->
    @if(isset($htmx_trigger))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        htmx.trigger(document.body, '{{ $htmx_trigger }}');
      });
    </script>
    @endif

    <script>
      // Configure HTMX
      document.body.addEventListener('htmx:configRequest', (event) => {
        event.detail.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
      });

      // Toast notifications
      document.body.addEventListener('taskCreated', () => showToast('Task created!', 'success'));
      document.body.addEventListener('taskUpdated', () => showToast('Task updated!', 'success'));
      document.body.addEventListener('taskDeleted', () => showToast('Task deleted!', 'info'));
      document.body.addEventListener('taskToggled', () =>
        showToast('Task status changed!', 'success')
      );

      function showToast(message, type = 'info') {
        const colors = {
          success: 'bg-green-500',
          error: 'bg-red-500',
          info: 'bg-blue-500',
          warning: 'bg-yellow-500',
        };

        const toast = document.createElement('div');
        toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        toast.textContent = message;

        document.getElementById('toast-container').appendChild(toast);

        setTimeout(() => toast.classList.remove('translate-x-full'), 10);
        setTimeout(() => {
          toast.classList.add('translate-x-full');
          setTimeout(() => toast.remove(), 300);
        }, 3000);
      }
    </script>

    @stack('scripts')
  </body>
</html>
