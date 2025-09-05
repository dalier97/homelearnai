<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Homeschool Hub') }}</title>

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
          <div class="flex items-center space-x-8">
            <h1 class="text-xl font-semibold text-gray-900">
              <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>{{ __('Homeschool Hub') }}</span>
              </a>
            </h1>

            <!-- Navigation Menu -->
            @if(session('user_id'))
            <nav class="hidden md:flex space-x-6">
              <!-- Parent Dashboard -->
              <a href="{{ route('dashboard.parent') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('dashboard.parent') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>{{ __('Dashboard') }}</span>
              </a>

              <a href="{{ route('children.index') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('children.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>{{ __('Children') }}</span>
              </a>

              <a href="{{ route('subjects.index') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('subjects.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>{{ __('Subjects') }}</span>
              </a>

              <a href="{{ route('planning.index') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('planning.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                <span>{{ __('Planning') }}</span>
              </a>

              <a href="{{ route('reviews.index') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('reviews.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span>{{ __('Reviews') }}</span>
              </a>

              <!-- Dropdown for Child Views (if has children) -->
              @php
                $userChildren = collect([]);
                // Only run complex database operations on authenticated pages, not on guest pages
                $isGuestRoute = in_array(request()->route()?->getName() ?? '', ['login', 'register', 'auth.confirm']);
                
                if (!$isGuestRoute && session('user_id') && session('supabase_token')) {
                  try {
                    $supabaseClient = app(\App\Services\SupabaseClient::class);
                    $accessToken = session('supabase_token');
                    $supabaseClient->setUserToken($accessToken);
                    $userChildren = \App\Models\Child::forUser(session('user_id'), $supabaseClient);
                  } catch (\Exception $e) {
                    // Silently fail for layout - don't break navigation
                    // Only log if Laravel is fully bootstrapped
                    if (function_exists('app') && app()->bound('log')) {
                      app('log')->debug('Failed to load children in layout', [
                        'error' => $e->getMessage(),
                        'has_session_token' => !empty(session('supabase_token')),
                        'has_user_id' => !empty(session('user_id')),
                      ]);
                    }
                  }
                }
              @endphp
              @if($userChildren->count() > 0)
                <div class="relative" x-data="{ open: false }">
                  <button @click="open = !open" 
                          class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>{{ __('Child Views') }}</span>
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                  <div x-show="open" @click.away="open = false" 
                       x-transition:enter="transition ease-out duration-100"
                       x-transition:enter-start="transform opacity-0 scale-95"
                       x-transition:enter-end="transform opacity-100 scale-100"
                       x-transition:leave="transition ease-in duration-75"
                       x-transition:leave-start="transform opacity-100 scale-100"
                       x-transition:leave-end="transform opacity-0 scale-95"
                       class="absolute top-full left-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 z-50">
                    @foreach($userChildren as $child)
                      <a href="{{ route('dashboard.child-today', $child->id) }}" 
                         class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                        {{ __(':name\'s Today', ['name' => $child->name]) }}
                      </a>
                    @endforeach
                  </div>
                </div>
              @endif

              <a href="{{ route('calendar.index') }}" 
                 class="flex items-center space-x-1 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md {{ request()->routeIs('calendar.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ __('Calendar') }}</span>
              </a>
            </nav>
            @endif
          </div>

          <div class="flex items-center space-x-4">
            <!-- Language Switcher -->
            @include('components.language-switcher')
            
            @if(session('user_id'))
            <span class="text-gray-700">{{ __('Welcome, :email', ['email' => session('user')['email'] ?? __('User')]) }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
              @csrf
              <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Logout') }}</button>
            </form>
            @endif
          </div>
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
        {{ __('Loading...') }}
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
      // Expose translations to JavaScript
      window.translations = @json(__('*'));
      window.currentLocale = '{{ app()->getLocale() }}';
      
      // Debug info for E2E tests
      @if(config('app.env') === 'testing')
      console.log('Layout: Translations loaded', {
        currentLocale: window.currentLocale,
        sampleTranslations: {
          login: window.translations.login || 'not found',
          'create account': window.translations['create account'] || 'not found'
        }
      });
      @endif
      
      // JavaScript translation helper
      window.__ = function(key, params = {}) {
        let translation = window.translations[key] || key;
        
        // Handle parameter replacement
        Object.keys(params).forEach(param => {
          const placeholder = ':' + param;
          translation = translation.replace(new RegExp(placeholder, 'g'), params[param]);
        });
        
        return translation;
      };
      
      // Dynamic translation reloading function
      window.reloadTranslations = async function(locale) {
        try {
          @if(config('app.env') === 'testing')
          console.log('Reloading translations for locale:', locale);
          @endif
          
          const response = await fetch('/translations/' + locale, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            credentials: 'same-origin'
          });
          
          if (!response.ok) {
            throw new Error('Translation loading failed: ' + response.status);
          }
          
          const data = await response.json();
          
          if (data.success) {
            // Update global translations
            window.translations = data.translations;
            window.currentLocale = locale;
            
            @if(config('app.env') === 'testing')
            console.log('Translations reloaded successfully', {
              locale: locale,
              translationCount: Object.keys(data.translations).length,
              sampleTranslations: {
                login: data.translations.login || 'not found',
                'Language changed successfully': data.translations['Language changed successfully'] || 'not found'
              }
            });
            @endif
            
            return true;
          } else {
            throw new Error(data.message || window.__('Failed to load translations'));
          }
        } catch (error) {
          console.error(window.__('Failed to reload translations:'), error);
          
          @if(config('app.env') === 'testing')
          console.log('Translation reload failed, will trigger fallback');
          @endif
          
          return false;
        }
      };
      
      // Enhanced translation update function
      window.updateAllTranslatableElements = function() {
        // Update elements with data-translate attributes
        document.querySelectorAll('[data-translate]').forEach(element => {
          const key = element.getAttribute('data-translate');
          if (key && window.translations[key]) {
            if (element.tagName === 'INPUT') {
              // For input placeholders
              if (element.hasAttribute('placeholder')) {
                element.placeholder = window.translations[key];
              } else {
                element.value = window.translations[key];
              }
            } else {
              element.textContent = window.translations[key];
            }
          }
        });
        
        // Update meta tags that depend on translations
        const titleKey = document.querySelector('meta[name="title-key"]');
        if (titleKey && window.translations[titleKey.content]) {
          document.title = window.translations[titleKey.content];
        }
        
        // Update aria-labels and alt texts
        document.querySelectorAll('[data-translate-aria]').forEach(element => {
          const key = element.getAttribute('data-translate-aria');
          if (key && window.translations[key]) {
            element.setAttribute('aria-label', window.translations[key]);
          }
        });
        
        document.querySelectorAll('[data-translate-alt]').forEach(element => {
          const key = element.getAttribute('data-translate-alt');
          if (key && window.translations[key]) {
            element.setAttribute('alt', window.translations[key]);
          }
        });
        
        // Trigger custom event for other components
        window.dispatchEvent(new CustomEvent('translations-updated', {
          detail: { locale: window.currentLocale }
        }));
        
        @if(config('app.env') === 'testing')
        console.log('All translatable elements updated for locale:', window.currentLocale);
        @endif
      };

      // Configure HTMX
      document.body.addEventListener('htmx:configRequest', (event) => {
        event.detail.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
      });

      // Toast notifications
      document.body.addEventListener('taskCreated', () => showToast('{{ __('Task created!') }}', 'success'));
      document.body.addEventListener('taskUpdated', () => showToast('{{ __('Task updated!') }}', 'success'));
      document.body.addEventListener('taskDeleted', () => showToast('{{ __('Task deleted!') }}', 'info'));
      document.body.addEventListener('taskToggled', () =>
        showToast('{{ __('Task status changed!') }}', 'success')
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
