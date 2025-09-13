<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Laravel Breeze auth routes are accessible
     */
    public function test_auth_routes_exist()
    {
        // Test Breeze routes (main auth system)
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * Test route naming is correct
     */
    public function test_route_names_are_correctly_configured()
    {
        // Breeze route names - use relative URLs
        $this->assertEquals('http://localhost:8000/login', route('login'));
        $this->assertEquals('http://localhost:8000/register', route('register'));
        $this->assertEquals('http://localhost:8000/logout', route('logout'));
    }

    /**
     * Test that forms include CSRF tokens
     */
    public function test_auth_forms_include_csrf_tokens()
    {
        // Test Breeze login form
        $response = $this->get('/login');
        $response->assertSee('_token');

        // Test Breeze register form
        $response = $this->get('/register');
        $response->assertSee('_token');
    }

    /**
     * Test that form submissions handle validation properly
     */
    public function test_form_validation_works()
    {
        // Test Breeze login validation
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/login', ['_token' => 'test-token']);
        $response->assertSessionHasErrors(['email', 'password']);

        // Test Breeze register validation
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/register', ['_token' => 'test-token']);
        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /**
     * Test that home redirect goes to main login route
     */
    public function test_home_redirect_works()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Test that guest middleware works correctly
     */
    public function test_guest_middleware_applied_correctly()
    {
        // Login and register routes should be accessible to guests
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    /**
     * Test route controllers are correct
     */
    public function test_route_controllers_are_correct()
    {
        $routes = \Route::getRoutes();

        // Test main login route uses Breeze controller
        $loginRoute = $routes->getByName('login');
        $this->assertStringContainsString('AuthenticatedSessionController', $loginRoute->getActionName());

        // Test main register route uses Breeze controller
        $registerRoute = $routes->getByName('register');
        $this->assertStringContainsString('RegisteredUserController', $registerRoute->getActionName());
    }

    /**
     * Test that password reset routes from Breeze work
     */
    public function test_breeze_password_reset_routes_work()
    {
        // Test forgot password form
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);

        // Test password reset form (need a token)
        $response = $this->get('/reset-password/test-token');
        $response->assertStatus(200);
    }
}
