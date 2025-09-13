<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a default CSRF token for tests
        $this->withSession(['_token' => 'test-csrf-token']);
    }

    /**
     * Helper method to make POST requests with CSRF token
     */
    protected function postWithCsrf($uri, array $data = [], array $headers = [])
    {
        return $this->post($uri, array_merge($data, ['_token' => 'test-csrf-token']), $headers);
    }

    /**
     * Helper method to make PATCH requests with CSRF token
     */
    protected function patchWithCsrf($uri, array $data = [], array $headers = [])
    {
        return $this->patch($uri, array_merge($data, ['_token' => 'test-csrf-token']), $headers);
    }

    /**
     * Helper method to make DELETE requests with CSRF token
     */
    protected function deleteWithCsrf($uri, array $data = [], array $headers = [])
    {
        return $this->delete($uri, array_merge($data, ['_token' => 'test-csrf-token']), $headers);
    }

    /**
     * Helper method to make PUT requests with CSRF token
     */
    protected function putWithCsrf($uri, array $data = [], array $headers = [])
    {
        return $this->put($uri, array_merge($data, ['_token' => 'test-csrf-token']), $headers);
    }
}
