# Debugging Guide for Laravel + Supabase

## 1. Enable Detailed Error Logging

### In SupabaseClient.php

Always return detailed error information from catch blocks:

```php
} catch (GuzzleException $e) {
    if ($e->hasResponse()) {
        $errorBody = $e->getResponse()->getBody()->getContents();
        $error = json_decode($errorBody, true);

        // Log the full error
        \Log::error('Supabase API Error', [
            'method' => 'signIn',
            'email' => $email,
            'status_code' => $e->getResponse()->getStatusCode(),
            'error_body' => $errorBody,
            'parsed_error' => $error
        ]);

        // Return structured error
        return [
            'error' => $error['msg'] ?? 'Unknown error',
            'details' => $error,
            'status_code' => $e->getResponse()->getStatusCode()
        ];
    }

    // Connection errors
    return [
        'error' => 'Connection failed: ' . $e->getMessage()
    ];
}
```

## 2. Add Debug Mode for API Calls

Create a debug wrapper for Supabase calls:

```php
private function debugApiCall(string $method, string $endpoint, array $data = [])
{
    \Log::debug('Supabase API Call', [
        'method' => $method,
        'endpoint' => $endpoint,
        'data' => $data
    ]);

    try {
        $response = $this->httpClient->$method($endpoint, ['json' => $data]);
        $body = $response->getBody()->getContents();

        \Log::debug('Supabase API Response', [
            'status' => $response->getStatusCode(),
            'body' => json_decode($body, true)
        ]);

        return json_decode($body, true);
    } catch (\Exception $e) {
        \Log::error('Supabase API Error', [
            'endpoint' => $endpoint,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

## 3. Monitor Multiple Log Sources

### Laravel Logs

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log

# Filter for Supabase errors
grep "Supabase" storage/logs/laravel.log

# Watch for specific errors
tail -f storage/logs/laravel.log | grep -E "(error|failed|exception)"
```

### Server Output

```bash
# Run server with verbose output
APP_DEBUG=true php artisan serve --host=0.0.0.0 --port=8000

# Or use Laravel's built-in debugging
php artisan serve --env=local
```

## 4. Create Test Scripts

Always create standalone test scripts for external services:

```php
// test-supabase.php
<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Test each operation independently
$client = new SupabaseClient(...);

// Test connection
echo "Testing connection...\n";
var_dump($client->from('profiles')->limit(1)->get());

// Test auth
echo "Testing auth...\n";
$result = $client->signIn('test@example.com', 'password');
if (isset($result['error'])) {
    echo "Error: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Success: Token = " . substr($result['access_token'], 0, 20) . "...\n";
}
```

## 5. Use Browser Developer Tools

### Network Tab

- Monitor API calls directly
- Check request/response headers
- See actual payloads and responses

### Console

Add JavaScript logging for HTMX requests:

```javascript
document.body.addEventListener('htmx:responseError', function (evt) {
  console.error('HTMX Error:', evt.detail);
});
```

## 6. Add Debug Endpoints

Create debug routes in development:

```php
// routes/web.php (only in local environment)
if (app()->environment('local')) {
    Route::get('/debug/supabase', function() {
        $client = app(SupabaseClient::class);

        return [
            'url' => config('services.supabase.url'),
            'has_anon_key' => !empty(config('services.supabase.anon_key')),
            'test_connection' => $client->from('profiles')->limit(1)->get()
        ];
    });
}
```

## 7. Environment-Specific Logging

```php
// In AuthController
if (config('app.debug')) {
    \Log::debug('Registration attempt', [
        'email' => $validated['email'],
        'supabase_url' => config('services.supabase.url'),
        'result_keys' => array_keys($result ?? []),
        'has_access_token' => isset($result['access_token']),
        'has_user' => isset($result['user']),
        'has_error' => isset($result['error'])
    ]);
}
```

## 8. Common Supabase Issues to Check

1. **No access_token in response**: Email confirmation required
2. **Invalid credentials**: Wrong password or email
3. **Email validation errors**: Supabase may block certain email patterns
4. **Connection refused**: Check URL and network
5. **401 Unauthorized**: Check API keys

## 9. Quick Debug Checklist

- [ ] Is APP_DEBUG=true in .env?
- [ ] Is error logging enabled?
- [ ] Are you checking the right log file?
- [ ] Is the error being caught and logged?
- [ ] Are you returning detailed errors in development?
- [ ] Have you tested the API call independently?
- [ ] Are you monitoring the right server instance?
