<?php

namespace App\Services;

/**
 * Temporary stub class to prevent crashes during migration
 * This class provides no-op methods for backwards compatibility
 */
class SupabaseClient
{
    public function __construct(
        private string $url = '',
        private string $anonKey = '',
        private string $serviceKey = ''
    ) {
        // Properties are stored for potential future use
    }

    /**
     * Check if the client is configured with valid credentials
     */
    public function isConfigured(): bool
    {
        return ! empty($this->url) && ! empty($this->anonKey) && ! empty($this->serviceKey);
    }

    public function setUserToken(string $accessToken): void
    {
        // No-op stub
    }

    public function from(string $table): self
    {
        return $this;
    }

    public function select(string $columns = '*'): self
    {
        return $this;
    }

    public function eq(string $column, $value): self
    {
        return $this;
    }

    public function single()
    {
        return null;
    }

    public function execute()
    {
        return collect([]);
    }

    public function insert(array $data): self
    {
        return $this;
    }

    public function update(array $data): self
    {
        return $this;
    }

    public function delete(): self
    {
        return $this;
    }

    public function rpc(string $functionName, array $parameters = []): self
    {
        return $this;
    }

    public function order(string $column, string $direction = 'asc'): self
    {
        return $this;
    }

    public function gte(string $column, $value): self
    {
        return $this;
    }

    public function limit(int $count): self
    {
        return $this;
    }

    public function get()
    {
        return collect([]);
    }
}
