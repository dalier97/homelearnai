<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;

class SupabaseClient
{
    private Client $httpClient;

    private string $baseUrl;

    private string $anonKey;

    /**
     * @param  string  $serviceKey  Reserved for future administrative operations
     *
     * @phpstan-ignore-next-line constructor.unusedParameter
     */
    public function __construct(string $url, string $anonKey, string $serviceKey)
    {
        $this->baseUrl = rtrim($url, '/');
        $this->anonKey = $anonKey;

        // Start with anon key, can be updated with setUserToken()
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer '.$this->anonKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ],
        ]);
    }

    /**
     * Set user access token for authenticated requests
     */
    public function setUserToken(string $accessToken): void
    {
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer '.$accessToken,  // Use user's access token
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ],
        ]);

        // Log token setting for debugging
        if (function_exists('app') && app() && app()->has('log')) {
            app('log')->debug('SupabaseClient: User token set', [
                'token_length' => strlen($accessToken),
                'token_prefix' => substr($accessToken, 0, 20).'...',
                'is_jwt' => str_starts_with($accessToken, 'eyJ'),
            ]);
        }
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
            // Return error details for better debugging
            if ($e instanceof RequestException && $e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $error = json_decode($errorBody, true);

                // Log only if in Laravel context with app initialized
                if (function_exists('app') && app() && app()->has('log')) {
                    app('log')->error('Supabase signIn failed', [
                        'email' => $email,
                        'error' => $e->getMessage(),
                        'response' => $errorBody,
                    ]);
                }

                return ['error' => $error['msg'] ?? $error['error'] ?? $error['error_description'] ?? 'Sign in failed', 'details' => $error];
            }

            return ['error' => 'Connection failed: '.$e->getMessage()];
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

            $result = json_decode($response->getBody()->getContents(), true);

            // Log successful signup for debugging
            \Log::info('Supabase signup successful', [
                'email' => $email,
                'has_user' => isset($result['user']),
                'has_token' => isset($result['access_token']),
                'has_session' => isset($result['session']),
            ]);

            return $result;
        } catch (GuzzleException $e) {
            // Return error details for debugging
            if ($e instanceof RequestException && $e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $error = json_decode($errorBody, true);

                // Log the full error for debugging
                \Log::error('Supabase signup failed', [
                    'email' => $email,
                    'status_code' => $e->getResponse()->getStatusCode(),
                    'error_body' => $errorBody,
                    'parsed_error' => $error,
                ]);

                // Provide more specific error messages based on common Supabase errors
                $errorMessage = 'Registration failed';

                if (isset($error['msg'])) {
                    $errorMessage = $error['msg'];
                } elseif (isset($error['error'])) {
                    $errorMessage = $error['error'];
                } elseif (isset($error['message'])) {
                    $errorMessage = $error['message'];
                } elseif (isset($error['error_description'])) {
                    $errorMessage = $error['error_description'];
                }

                // Handle specific error cases with clearer messages
                if (str_contains(strtolower($errorMessage), 'user already registered')) {
                    $errorMessage = __('An account with this email already exists. Please log in instead.');
                } elseif (str_contains(strtolower($errorMessage), 'password')) {
                    $errorMessage = __('Password must be at least 8 characters long.');
                } elseif (str_contains(strtolower($errorMessage), 'email')) {
                    $errorMessage = __('Please provide a valid email address.');
                } elseif (str_contains(strtolower($errorMessage), 'database')) {
                    // Check if it's a database lookup error that's not critical
                    if (str_contains(strtolower($errorMessage), 'finding user')) {
                        // This might be a lookup after successful registration
                        \Log::warning('Post-registration user lookup failed, but user might be created', [
                            'email' => $email,
                            'error' => $errorMessage,
                        ]);

                        // Don't return error here, let the registration flow continue
                        return ['warning' => $errorMessage];
                    }
                    $errorMessage = __('A database error occurred. Please try again later.');
                }

                return ['error' => $errorMessage];
            }

            \Log::error('Supabase signup connection failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ['error' => __('Unable to connect to the authentication service. Please try again.')];
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
                    'Authorization' => 'Bearer '.$accessToken,
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
            throw new \Exception('RPC call failed: '.$e->getMessage());
        }
    }

    /**
     * Subscribe to realtime changes
     */
    public function getRealtimeUrl(): string
    {
        return str_replace('https://', 'wss://', $this->baseUrl).'/realtime/v1';
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

    public function gte(string $column, mixed $value): self
    {
        $this->query[$column] = "gte.{$value}";

        return $this;
    }

    public function lte(string $column, mixed $value): self
    {
        $this->query[$column] = "lte.{$value}";

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

    public function order(string $column, string $direction = 'asc'): self
    {
        return $this->orderBy($column, $direction);
    }

    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    public function single(): ?array
    {
        $this->headers['Accept'] = 'application/vnd.pgrst.object+json';
        $result = $this->executeSingle();

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

            // Log insert results for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->debug('SupabaseClient insert executed', [
                    'table' => $this->table,
                    'data' => $data,
                    'result' => $result,
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            return is_array($result) ? $result : [$result];
        } catch (GuzzleException $e) {
            // Log the error for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                $errorBody = '';
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                }

                app('log')->error('SupabaseClient insert failed', [
                    'table' => $this->table,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'response_body' => $errorBody,
                    'status_code' => ($e instanceof RequestException && $e->hasResponse()) ? $e->getResponse()->getStatusCode() : null,
                ]);
            }
            throw new \Exception('Insert failed: '.$e->getMessage());
        }
    }

    public function update(array $data): array
    {
        try {
            // Log update attempt for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->debug('SupabaseClient update attempt', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'data' => $data,
                ]);
            }

            $options = ['json' => $data];

            // Add query parameters if any exist (for WHERE conditions)
            if (! empty($this->query)) {
                $options['query'] = $this->query;
            }

            $response = $this->client->patch("/rest/v1/{$this->table}", $options);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log successful update
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->debug('SupabaseClient update executed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'data' => $data,
                    'result' => $result,
                    'status_code' => $response->getStatusCode(),
                    'result_count' => is_array($result) ? count($result) : 0,
                ]);
            }

            return is_array($result) ? $result : [$result];
        } catch (GuzzleException $e) {
            // Log the error for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                $errorBody = '';
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                }

                app('log')->error('SupabaseClient update failed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'response_body' => $errorBody,
                    'status_code' => ($e instanceof RequestException && $e->hasResponse()) ? $e->getResponse()->getStatusCode() : null,
                ]);
            }

            throw new \Exception('Update failed: '.$e->getMessage());
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
            throw new \Exception('Delete failed: '.$e->getMessage());
        }
    }

    private function executeSingle(): mixed
    {
        try {
            $response = $this->client->get("/rest/v1/{$this->table}", [
                'query' => $this->query,
                'headers' => $this->headers,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log query results for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->debug('SupabaseClient single query executed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'has_result' => ! empty($result),
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            return $result;
        } catch (GuzzleException $e) {
            // Handle 406 Not Acceptable gracefully for single() - this means 0 rows
            if ($e instanceof RequestException && $e->hasResponse() && $e->getResponse()->getStatusCode() === 406) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($errorBody, true);

                // This is expected when no rows match - not an error
                if (isset($errorData['code']) && $errorData['code'] === 'PGRST116') {
                    if (function_exists('app') && app() && app()->has('log')) {
                        app('log')->debug('SupabaseClient single query - no rows found', [
                            'table' => $this->table,
                            'query' => $this->query,
                        ]);
                    }

                    return null;
                }
            }

            // Log actual errors for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                $errorBody = '';
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                }

                app('log')->error('SupabaseClient single query failed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'error' => $e->getMessage(),
                    'response_body' => $errorBody,
                    'status_code' => ($e instanceof RequestException && $e->hasResponse()) ? $e->getResponse()->getStatusCode() : null,
                ]);
            }

            return null;
        }
    }

    private function execute(): mixed
    {
        try {
            $response = $this->client->get("/rest/v1/{$this->table}", [
                'query' => $this->query,
                'headers' => $this->headers,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log query results for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->debug('SupabaseClient query executed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'result_count' => is_array($result) ? count($result) : 0,
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            return $result;
        } catch (GuzzleException $e) {
            // Log the error for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                $errorBody = '';
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                }

                app('log')->error('SupabaseClient query failed', [
                    'table' => $this->table,
                    'query' => $this->query,
                    'error' => $e->getMessage(),
                    'response_body' => $errorBody,
                    'status_code' => ($e instanceof RequestException && $e->hasResponse()) ? $e->getResponse()->getStatusCode() : null,
                ]);
            }

            return null;
        }
    }
}
