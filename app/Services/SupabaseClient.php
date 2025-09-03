<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

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
                'Authorization' => 'Bearer '.$this->anonKey,
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
            // Return error details for better debugging
            if ($e->hasResponse()) {
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

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            // Return error details for debugging
            if ($e->hasResponse()) {
                $error = json_decode($e->getResponse()->getBody()->getContents(), true);

                return ['error' => $error['msg'] ?? $error['error'] ?? 'Registration failed'];
            }

            return ['error' => 'Connection failed'];
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
            throw new \Exception('Insert failed: '.$e->getMessage());
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
