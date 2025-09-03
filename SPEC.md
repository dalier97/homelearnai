# Laravel + Supabase + HTMX SaaS Application with Playwright Tests

A modern task management SaaS using Laravel, Supabase for database/auth, HTMX for interactivity, and Playwright for E2E testing.

## Project Setup

```bash
# Create new Laravel project
composer create-project laravel/laravel taskmaster-laravel
cd taskmaster-laravel

# Install required packages
composer require guzzlehttp/guzzle
composer require laravel/sanctum
composer require --dev laravel/pint

# Install Node dependencies
npm install
npm install --save-dev @playwright/test
```

## Environment Configuration (.env)

```env
APP_NAME=TaskMaster
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Supabase Configuration
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-key

# Use Supabase as primary database (optional)
DB_CONNECTION=pgsql
DB_HOST=db.your-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-password

# Cache & Session (use Redis in production)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## Supabase Service Provider

### app/Providers/SupabaseServiceProvider.php

```php
<?php

namespace App\Providers;

use App\Services\SupabaseClient;
use Illuminate\Support\ServiceProvider;

class SupabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupabaseClient::class, function ($app) {
            return new SupabaseClient(
                config('services.supabase.url'),
                config('services.supabase.anon_key'),
                config('services.supabase.service_key')
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
```

## Supabase Client Service

### app/Services/SupabaseClient.php

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SupabaseClient
{
    private Client $httpClient;
    private string $baseUrl;
    private string $anonKey;
    private string $serviceKey;

    public function __construct(string $url, string $anonKey, string $serviceKey)
    {
        $this->baseUrl = rtrim($url, '/');
        $this->anonKey = $anonKey;
        $this->serviceKey = $serviceKey;
        
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer ' . $this->anonKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ],
        ]);
    }

    /**
     * Authenticate user with Supabase
     */
    public function signIn(string $email, string $password): ?array
    {
        try {
            $response = $this->httpClient->post('/auth/v1/token?grant_type=password', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Sign up new user
     */
    public function signUp(string $email, string $password, array $metadata = []): ?array
    {
        try {
            $response = $this->httpClient->post('/auth/v1/signup', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'data' => $metadata,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Get user by access token
     */
    public function getUser(string $accessToken): ?array
    {
        try {
            $response = $this->httpClient->get('/auth/v1/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Query builder for Supabase tables
     */
    public function from(string $table): SupabaseQueryBuilder
    {
        return new SupabaseQueryBuilder($this->httpClient, $table);
    }

    /**
     * Call Supabase RPC function
     */
    public function rpc(string $function, array $params = []): mixed
    {
        try {
            $response = $this->httpClient->post("/rest/v1/rpc/{$function}", [
                'json' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception("RPC call failed: " . $e->getMessage());
        }
    }

    /**
     * Subscribe to realtime changes
     */
    public function getRealtimeUrl(): string
    {
        return str_replace('https://', 'wss://', $this->baseUrl) . '/realtime/v1';
    }
}

class SupabaseQueryBuilder
{
    private Client $client;
    private string $table;
    private array $query = [];
    private array $headers = [];

    public function __construct(Client $client, string $table)
    {
        $this->client = $client;
        $this->table = $table;
    }

    public function select(string $columns = '*'): self
    {
        $this->query['select'] = $columns;
        return $this;
    }

    public function eq(string $column, mixed $value): self
    {
        $this->query[$column] = "eq.{$value}";
        return $this;
    }

    public function neq(string $column, mixed $value): self
    {
        $this->query[$column] = "neq.{$value}";
        return $this;
    }

    public function gt(string $column, mixed $value): self
    {
        $this->query[$column] = "gt.{$value}";
        return $this;
    }

    public function lt(string $column, mixed $value): self
    {
        $this->query[$column] = "lt.{$value}";
        return $this;
    }

    public function like(string $column, string $pattern): self
    {
        $this->query[$column] = "like.{$pattern}";
        return $this;
    }

    public function in(string $column, array $values): self
    {
        $valuesList = implode(',', $values);
        $this->query[$column] = "in.({$valuesList})";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query['order'] = "{$column}.{$direction}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    public function single(): ?array
    {
        $this->headers['Accept'] = 'application/vnd.pgrst.object+json';
        $result = $this->execute();
        return $result ?: null;
    }

    public function get(): Collection
    {
        $result = $this->execute();
        return collect($result ?: []);
    }

    public function first(): ?array
    {
        return $this->limit(1)->get()->first();
    }

    public function insert(array $data): array
    {
        try {
            $response = $this->client->post("/rest/v1/{$this->table}", [
                'json' => $data,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return is_array($result) ? $result : [$result];
        } catch (GuzzleException $e) {
            throw new \Exception("Insert failed: " . $e->getMessage());
        }
    }

    public function update(array $data): array
    {
        try {
            $response = $this->client->patch("/rest/v1/{$this->table}", [
                'json' => $data,
                'query' => $this->query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception("Update failed: " . $e->getMessage());
        }
    }

    public function delete(): bool
    {
        try {
            $response = $this->client->delete("/rest/v1/{$this->table}", [
                'query' => $this->query,
            ]);

            return $response->getStatusCode() === 204;
        } catch (GuzzleException $e) {
            throw new \Exception("Delete failed: " . $e->getMessage());
        }
    }

    private function execute(): mixed
    {
        try {
            $response = $this->client->get("/rest/v1/{$this->table}", [
                'query' => $this->query,
                'headers' => $this->headers,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return null;
        }
    }
}
```

## Models

### app/Models/Task.php

```php
<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Task
{
    public ?int $id = null;
    public string $title;
    public ?string $description;
    public string $priority = 'medium';
    public string $status = 'pending';
    public int $user_id;
    public ?Carbon $due_date = null;
    public ?Carbon $completed_at = null;
    public ?Carbon $created_at = null;
    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['due_date', 'completed_at', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('tasks')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('tasks')
            ->eq($column, $value)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => new self($item));
    }

    public static function forUser(int $userId, SupabaseClient $supabase): Collection
    {
        return self::where('user_id', $userId, $supabase);
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'due_date' => $this->due_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('tasks')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('tasks')->insert($data);
            if ($result && isset($result[0]['id'])) {
                $this->id = $result[0]['id'];
                $this->created_at = Carbon::now();
            }
        }

        return !empty($result);
    }

    public function delete(SupabaseClient $supabase): bool
    {
        if (!$this->id) return false;
        
        return $supabase->from('tasks')
            ->eq('id', $this->id)
            ->delete();
    }

    public function toggleComplete(): void
    {
        if ($this->status === 'completed') {
            $this->status = 'pending';
            $this->completed_at = null;
        } else {
            $this->status = 'completed';
            $this->completed_at = Carbon::now();
        }
    }

    public function isOverdue(): bool
    {
        return $this->due_date 
            && $this->due_date->isPast() 
            && $this->status !== 'completed';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'due_date' => $this->due_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
        ];
    }
}
```

## Controllers

### app/Http/Controllers/TaskController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function index(Request $request): View
    {
        $tasks = Task::forUser(Auth::id(), $this->supabase);
        
        // Filter by search
        if ($search = $request->get('search')) {
            $tasks = $tasks->filter(function ($task) use ($search) {
                return str_contains(strtolower($task->title), strtolower($search)) ||
                       str_contains(strtolower($task->description ?? ''), strtolower($search));
            });
        }

        // Filter by priority
        if ($priority = $request->get('priority')) {
            $tasks = $tasks->where('priority', $priority);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $tasks = $tasks->where('status', $status);
        }

        // If HTMX request, return partial
        if ($request->header('HX-Request')) {
            return view('tasks.partials.list', compact('tasks'));
        }

        return view('tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        return view('tasks.partials.form', ['task' => new Task()]);
    }

    public function store(Request $request): View
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task = new Task($validated);
        $task->user_id = Auth::id();
        $task->save($this->supabase);

        // Return the new task item for HTMX
        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskCreated');
    }

    public function edit(int $id): View
    {
        $task = Task::find($id, $this->supabase);
        
        if (!$task || $task->user_id !== Auth::id()) {
            abort(404);
        }

        return view('tasks.partials.form', compact('task'));
    }

    public function update(Request $request, int $id): View
    {
        $task = Task::find($id, $this->supabase);
        
        if (!$task || $task->user_id !== Auth::id()) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        foreach ($validated as $key => $value) {
            $task->$key = $value;
        }
        
        $task->save($this->supabase);

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskUpdated');
    }

    public function toggle(int $id): View
    {
        $task = Task::find($id, $this->supabase);
        
        if (!$task || $task->user_id !== Auth::id()) {
            abort(404);
        }

        $task->toggleComplete();
        $task->save($this->supabase);

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskToggled');
    }

    public function destroy(int $id): string
    {
        $task = Task::find($id, $this->supabase);
        
        if (!$task || $task->user_id !== Auth::id()) {
            abort(404);
        }

        $task->delete($this->supabase);

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'taskDeleted')->send();
        return '';
    }
}
```

### app/Http/Controllers/AuthController.php

```php
<?php

namespace App\Http\Controllers;

use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->supabase->signIn($validated['email'], $validated['password']);

        if ($result && isset($result['access_token'])) {
            // Store token in session
            Session::put('supabase_token', $result['access_token']);
            Session::put('user', $result['user']);
            
            // Create Laravel auth session
            Auth::loginUsingId($result['user']['id']);

            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'name' => 'required|string|max:255',
        ]);

        $result = $this->supabase->signUp(
            $validated['email'], 
            $validated['password'],
            ['name' => $validated['name']]
        );

        if ($result && isset($result['access_token'])) {
            Session::put('supabase_token', $result['access_token']);
            Session::put('user', $result['user']);
            Auth::loginUsingId($result['user']['id']);

            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Registration failed']);
    }

    public function logout()
    {
        Session::forget('supabase_token');
        Session::forget('user');
        Auth::logout();
        
        return redirect()->route('login');
    }
}
```

## Views (Blade Templates with HTMX)

### resources/views/layouts/app.blade.php

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TaskMaster') }}</title>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: inline-block; }
        .htmx-swapping { opacity: 0; transition: opacity 200ms ease-out; }
        
        .priority-urgent { border-left: 4px solid #ef4444; }
        .priority-high { border-left: 4px solid #f59e0b; }
        .priority-medium { border-left: 4px solid #3b82f6; }
        .priority-low { border-left: 4px solid #6b7280; }
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
                    <span class="text-gray-700">
                        Welcome, {{ session('user')['email'] ?? 'User' }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            Logout
                        </button>
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
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- HTMX Response Headers -->
    @if(isset($htmx_trigger))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
        document.body.addEventListener('taskToggled', () => showToast('Task status changed!', 'success'));

        function showToast(message, type = 'info') {
            const colors = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'info': 'bg-blue-500',
                'warning': 'bg-yellow-500'
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
```

### resources/views/tasks/index.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">My Tasks</h2>
        <button 
            hx-get="{{ route('tasks.create') }}" 
            hx-target="#task-modal" 
            hx-swap="innerHTML"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition"
        >
            + New Task
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex flex-col md:flex-row gap-4">
            <input 
                type="text" 
                name="search"
                placeholder="Search tasks..."
                hx-get="{{ route('tasks.index') }}"
                hx-trigger="keyup changed delay:500ms"
                hx-target="#task-list"
                hx-swap="innerHTML"
                class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            
            <select 
                name="priority"
                hx-get="{{ route('tasks.index') }}"
                hx-trigger="change"
                hx-target="#task-list"
                hx-include="[name='search']"
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All Priorities</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            
            <select 
                name="status"
                hx-get="{{ route('tasks.index') }}"
                hx-trigger="change"
                hx-target="#task-list"
                hx-include="[name='search'], [name='priority']"
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </div>

    <!-- Task List -->
    <div id="task-list" class="space-y-2">
        @include('tasks.partials.list')
    </div>
</div>

<!-- Modal Container -->
<div id="task-modal"></div>
@endsection
```

### resources/views/tasks/partials/list.blade.php

```blade
@forelse($tasks as $task)
    @include('tasks.partials.item', ['task' => $task])
@empty
    <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
        <p class="text-xl mb-4">No tasks found</p>
        <p>Create your first task to get started!</p>
    </div>
@endforelse
```

### resources/views/tasks/partials/item.blade.php

```blade
@php
    $priorityColors = [
        'urgent' => 'text-red-600 bg-red-50',
        'high' => 'text-orange-600 bg-orange-50',
        'medium' => 'text-blue-600 bg-blue-50',
        'low' => 'text-gray-600 bg-gray-50',
    ];
    
    $statusIcons = [
        'pending' => '○',
        'in_progress' => '◐',
        'completed' => '●',
    ];
@endphp

<div 
    id="task-{{ $task->id }}"
    class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow priority-{{ $task->priority }} {{ $task->status === 'completed' ? 'opacity-75' : '' }}"
>
    <div class="flex items-start justify-between">
        <div class="flex items-start space-x-3 flex-1">
            <!-- Toggle Status -->
            <button 
                hx-patch="{{ route('tasks.toggle', $task->id) }}"
                hx-target="#task-{{ $task->id }}"
                hx-swap="outerHTML"
                class="mt-1 text-2xl hover:scale-110 transition-transform"
            >
                <span class="{{ $task->status === 'completed' ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $statusIcons[$task->status] }}
                </span>
            </button>
            
            <!-- Content -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-gray-900 {{ $task->status === 'completed' ? 'line-through' : '' }}">
                        {{ $task->title }}
                    </h3>
                    
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $priorityColors[$task->priority] }}">
                        {{ ucfirst($task->priority) }}
                    </span>
                    
                    @if($task->isOverdue())
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                            Overdue
                        </span>
                    @endif
                </div>
                
                @if($task->description)
                    <p class="text-gray-600 text-sm mb-2 {{ $task->status === 'completed' ? 'line-through' : '' }}">
                        {{ $task->description }}
                    </p>
                @endif
                
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    @if($task->due_date)
                        <span>Due: {{ $task->due_date->format('M j, Y') }}</span>
                    @endif
                    
                    @if($task->completed_at)
                        <span>Completed: {{ $task->completed_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center space-x-2 ml-4">
            <button 
                hx-get="{{ route('tasks.edit', $task->id) }}"
                hx-target="#task-modal"
                hx-swap="innerHTML"
                class="text-gray-400 hover:text-blue-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </button>
            
            <button 
                hx-delete="{{ route('tasks.destroy', $task->id) }}"
                hx-target="#task-{{ $task->id }}"
                hx-swap="outerHTML swap:1s"
                hx-confirm="Delete this task?"
                class="text-gray-400 hover:text-red-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
```

### resources/views/tasks/partials/form.blade.php

```blade
<div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open">
    <div class="flex items-center justify-center min-h-screen px-4">
        <!-- Backdrop -->
        <div 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="open = false; document.getElementById('task-modal').innerHTML = ''"
        ></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">
                {{ $task->id ? 'Edit Task' : 'New Task' }}
            </h3>

            <form 
                hx-post="{{ $task->id ? route('tasks.update', $task->id) : route('tasks.store') }}"
                @if($task->id) hx-put="{{ route('tasks.update', $task->id) }}" @endif
                hx-target="#task-list"
                hx-swap="innerHTML"
                hx-on::after-request="document.getElementById('task-modal').innerHTML = ''"
            >
                @csrf
                @if($task->id) @method('PUT') @endif

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input 
                            type="text" 
                            name="title" 
                            value="{{ $task->title }}"
                            required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea 
                            name="description" 
                            rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >{{ $task->description }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select 
                            name="priority"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ $task->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input 
                            type="date" 
                            name="due_date" 
                            value="{{ $task->due_date?->format('Y-m-d') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button 
                        type="button"
                        @click="open = false; document.getElementById('task-modal').innerHTML = ''"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        {{ $task->id ? 'Update' : 'Create' }} Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

## Routes

### routes/web.php

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [TaskController::class, 'index'])->name('dashboard');
    
    // Task routes
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{id}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
```

## Supabase Database Schema

```sql
-- Create users table (if not using Supabase Auth)
CREATE TABLE IF NOT EXISTS users (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    name TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    description TEXT,
    priority TEXT DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled')),
    due_date TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_priority ON tasks(priority);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);

-- Enable Row Level Security
ALTER TABLE tasks ENABLE ROW LEVEL SECURITY;

-- Create RLS policies
CREATE POLICY "Users can view own tasks" ON tasks
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create own tasks" ON tasks
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own tasks" ON tasks
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own tasks" ON tasks
    FOR DELETE USING (auth.uid() = user_id);

-- Create realtime publication
ALTER PUBLICATION supabase_realtime ADD TABLE tasks;
```

## Playwright E2E Tests

### tests/e2e/tasks.spec.js

```javascript
import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:8000';
const TEST_USER = {
  email: 'test@example.com',
  password: 'Test123456!',
};

test.describe('Task Management E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/dashboard`);
  });

  test('should display dashboard with task list', async ({ page }) => {
    await expect(page.locator('h2')).toContainText('My Tasks');
    await expect(page.locator('button:has-text("+ New Task")')).toBeVisible();
  });

  test('should create a new task', async ({ page }) => {
    // Click new task button
    await page.click('button:has-text("+ New Task")');
    
    // Wait for modal
    await page.waitForSelector('#task-modal form');
    
    // Fill form
    await page.fill('input[name="title"]', 'Test Task');
    await page.fill('textarea[name="description"]', 'This is a test task');
    await page.selectOption('select[name="priority"]', 'high');
    await page.fill('input[name="due_date"]', '2025-12-31');
    
    // Submit form
    await page.click('button:has-text("Create Task")');
    
    // Verify task appears in list
    await expect(page.locator('#task-list')).toContainText('Test Task');
    await expect(page.locator('#task-list')).toContainText('This is a test task');
    
    // Verify toast notification
    await expect(page.locator('#toast-container')).toContainText('Task created!');
  });

  test('should edit an existing task', async ({ page }) => {
    // Assuming a task exists, click edit button
    await page.click('#task-list button[hx-get*="edit"]:first');
    
    // Wait for modal
    await page.waitForSelector('#task-modal form');
    
    // Update title
    await page.fill('input[name="title"]', 'Updated Task Title');
    
    // Submit form
    await page.click('button:has-text("Update Task")');
    
    // Verify update
    await expect(page.locator('#task-list')).toContainText('Updated Task Title');
    await expect(page.locator('#toast-container')).toContainText('Task updated!');
  });

  test('should toggle task completion status', async ({ page }) => {
    // Click toggle button on first task
    const toggleButton = page.locator('#task-list button[hx-patch*="toggle"]:first');
    const initialClass = await toggleButton.locator('span').getAttribute('class');
    
    await toggleButton.click();
    
    // Wait for update
    await page.waitForTimeout(500);
    
    // Check that class has changed
    const newClass = await toggleButton.locator('span').getAttribute('class');
    expect(newClass).not.toBe(initialClass);
    
    // Verify toast
    await expect(page.locator('#toast-container')).toContainText('Task status changed!');
  });

  test('should delete a task with confirmation', async ({ page }) => {
    // Count initial tasks
    const initialCount = await page.locator('#task-list > div').count();
    
    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());
    
    // Click delete on first task
    await page.click('#task-list button[hx-delete]:first');
    
    // Wait for deletion
    await page.waitForTimeout(1000);
    
    // Verify task count decreased
    const newCount = await page.locator('#task-list > div').count();
    expect(newCount).toBe(initialCount - 1);
    
    // Verify toast
    await expect(page.locator('#toast-container')).toContainText('Task deleted!');
  });

  test('should filter tasks by search', async ({ page }) => {
    // Type in search box
    await page.fill('input[name="search"]', 'urgent');
    
    // Wait for HTMX to update (500ms delay + request time)
    await page.waitForTimeout(1000);
    
    // Verify filtered results
    const tasks = await page.locator('#task-list > div').count();
    
    // All visible tasks should contain 'urgent' in title or description
    for (let i = 0; i < tasks; i++) {
      const taskText = await page.locator(`#task-list > div:nth-child(${i + 1})`).textContent();
      expect(taskText.toLowerCase()).toContain('urgent');
    }
  });

  test('should filter tasks by priority', async ({ page }) => {
    // Select high priority
    await page.selectOption('select[name="priority"]', 'high');
    
    // Wait for update
    await page.waitForTimeout(500);
    
    // Verify all tasks have high priority badge
    const badges = page.locator('#task-list .bg-orange-50:has-text("High")');
    const tasksCount = await page.locator('#task-list > div').count();
    const badgesCount = await badges.count();
    
    expect(badgesCount).toBe(tasksCount);
  });

  test('should filter tasks by status', async ({ page }) => {
    // Select completed status
    await page.selectOption('select[name="status"]', 'completed');
    
    // Wait for update
    await page.waitForTimeout(500);
    
    // Verify all tasks have completed styling (line-through)
    const tasks = page.locator('#task-list .line-through');
    expect(await tasks.count()).toBeGreaterThan(0);
  });

  test('should show overdue indicator for past due dates', async ({ page }) => {
    // Create a task with past due date
    await page.click('button:has-text("+ New Task")');
    await page.waitForSelector('#task-modal form');
    
    await page.fill('input[name="title"]', 'Overdue Task');
    await page.fill('input[name="due_date"]', '2020-01-01');
    await page.click('button:has-text("Create Task")');
    
    // Verify overdue badge appears
    await expect(page.locator('#task-list')).toContainText('Overdue');
    await expect(page.locator('.bg-red-100:has-text("Overdue")')).toBeVisible();
  });

  test('should handle concurrent updates without conflicts', async ({ page, context }) => {
    // Open second tab
    const page2 = await context.newPage();
    await page2.goto(`${BASE_URL}/login`);
    await page2.fill('input[name="email"]', TEST_USER.email);
    await page2.fill('input[name="password"]', TEST_USER.password);
    await page2.click('button[type="submit"]');
    await page2.waitForURL(`${BASE_URL}/dashboard`);
    
    // Create task in first tab
    await page.click('button:has-text("+ New Task")');
    await page.waitForSelector('#task-modal form');
    await page.fill('input[name="title"]', 'Concurrent Task 1');
    await page.click('button:has-text("Create Task")');
    
    // Create task in second tab
    await page2.click('button:has-text("+ New Task")');
    await page2.waitForSelector('#task-modal form');
    await page2.fill('input[name="title"]', 'Concurrent Task 2');
    await page2.click('button:has-text("Create Task")');
    
    // Refresh first tab and verify both tasks appear
    await page.reload();
    await expect(page.locator('#task-list')).toContainText('Concurrent Task 1');
    await expect(page.locator('#task-list')).toContainText('Concurrent Task 2');
    
    await page2.close();
  });
});

test.describe('Authentication E2E Tests', () => {
  test('should redirect to login when not authenticated', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`);
    await expect(page).toHaveURL(`${BASE_URL}/login`);
  });

  test('should login successfully with valid credentials', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    
    await expect(page).toHaveURL(`${BASE_URL}/dashboard`);
    await expect(page.locator('nav')).toContainText(TEST_USER.email);
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'wrong@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('.text-red-600')).toContainText('Invalid credentials');
    await expect(page).toHaveURL(`${BASE_URL}/login`);
  });

  test('should logout successfully', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/dashboard`);
    
    // Logout
    await page.click('button:has-text("Logout")');
    await expect(page).toHaveURL(`${BASE_URL}/login`);
    
    // Verify can't access dashboard
    await page.goto(`${BASE_URL}/dashboard`);
    await expect(page).toHaveURL(`${BASE_URL}/login`);
  });
});
```

### playwright.config.js

```javascript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    // Mobile testing
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  webServer: {
    command: 'php artisan serve',
    url: 'http://localhost:8000',
    reuseExistingServer: !process.env.CI,
  },
});
```

## Package.json Scripts

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:e2e:debug": "playwright test --debug",
    "test:e2e:report": "playwright show-report"
  },
  "devDependencies": {
    "@playwright/test": "^1.40.0",
    "autoprefixer": "^10.4.14",
    "axios": "^1.1.2",
    "laravel-vite-plugin": "^0.8.0",
    "postcss": "^8.4.31",
    "tailwindcss": "^3.3.0",
    "vite": "^4.0.0"
  }
}
```

## Running the Application

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations (if using Supabase as primary DB)
php artisan migrate

# Start development server
php artisan serve

# In another terminal, run E2E tests
npm run test:e2e

# Or with UI mode for debugging
npm run test:e2e:ui
```

## Key Features Demonstrated

### 1. **Laravel Benefits**
- Clean routing with named routes
- Service container with DI
- Blade templating with components
- CSRF protection built-in
- Validation with form requests
- Service providers for configuration

### 2. **Supabase Integration**
- Authentication via Supabase Auth
- Real-time subscriptions ready
- Row Level Security support
- Direct database queries
- RPC function calls

### 3. **HTMX Features**
- Dynamic CRUD without page refresh
- Search with debounce
- Confirmation dialogs
- Toast notifications via events
- Partial template rendering
- Progressive enhancement

### 4. **E2E Testing with Playwright**
- Complete user journey tests
- Authentication flow testing
- CRUD operations testing
- Filter and search testing
- Concurrent update handling
- Mobile device testing
- Visual regression testing

### 5. **Production Ready**
- Environment configuration
- Database migrations
- Error handling
- Security (CSRF, validation)
- Responsive design
- Loading states

## Performance Optimizations

```php
// Add caching
use Illuminate\Support\Facades\Cache;

public function index(Request $request)
{
    $cacheKey = 'tasks_' . Auth::id() . '_' . md5($request->getQueryString());
    
    $tasks = Cache::remember($cacheKey, 300, function () {
        return Task::forUser(Auth::id(), $this->supabase);
    });
    
    // Clear cache on updates
    Cache::forget('tasks_' . Auth::id() . '*');
}

// Add query optimization
$tasks = $this->supabase->from('tasks')
    ->select('*, user:users(email, name)')  // Join user data
    ->eq('user_id', Auth::id())
    ->orderBy('created_at', 'desc')
    ->get();
```

This complete example shows how Laravel's productivity features combined with Supabase's backend services and HTMX's interactivity create a powerful, modern SaaS application with comprehensive testing.
