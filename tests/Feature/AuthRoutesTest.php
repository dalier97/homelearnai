<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that both Breeze and Supabase auth routes are accessible
     */
    public function test_both_auth_systems_routes_exist()
    {
        // Test Breeze routes (main auth system)
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');

        // Test Legacy Supabase routes (transition system)
        $response = $this->get('/auth/supabase/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');

        $response = $this->get('/auth/supabase/register');
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * Test route naming is correct
     */
    public function test_route_names_are_correctly_configured()
    {
        // Breeze route names (default) - use relative URLs
        $this->assertEquals('http://localhost:8000/login', route('login'));
        $this->assertEquals('http://localhost:8000/register', route('register'));

        // Legacy Supabase route names (prefixed)
        $this->assertEquals('http://localhost:8000/auth/supabase/login', route('supabase.login'));
        $this->assertEquals('http://localhost:8000/auth/supabase/register', route('supabase.register'));
        $this->assertEquals('http://localhost:8000/auth/supabase/logout', route('supabase.logout'));
        $this->assertEquals('http://localhost:8000/auth/supabase/confirm', route('supabase.confirm'));
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

        // Test Legacy Supabase login form
        $response = $this->get('/auth/supabase/login');
        $response->assertSee('_token');

        // Test Legacy Supabase register form
        $response = $this->get('/auth/supabase/register');
        $response->assertSee('_token');
    }

    /**
     * Test that form submissions handle validation properly
     */
    public function test_form_validation_works_on_both_systems()
    {
        // Test Breeze login validation
        $response = $this->post('/login', []);
        $response->assertSessionHasErrors(['email', 'password']);

        // Test Breeze register validation
        $response = $this->post('/register', []);
        $response->assertSessionHasErrors(['name', 'email', 'password']);

        // Test Legacy Supabase login validation
        $response = $this->post('/auth/supabase/login', []);
        $response->assertSessionHasErrors(['email', 'password']);

        // Test Legacy Supabase register validation
        $response = $this->post('/auth/supabase/register', []);
        $response->assertSessionHasErrors(['email', 'password', 'name']);
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
     * Test that guest middleware works on both systems
     */
    public function test_guest_middleware_applied_correctly()
    {
        // Both login routes should be accessible to guests
        $this->get('/login')->assertStatus(200);
        $this->get('/auth/supabase/login')->assertStatus(200);

        // Both register routes should be accessible to guests
        $this->get('/register')->assertStatus(200);
        $this->get('/auth/supabase/register')->assertStatus(200);
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

        // Test legacy login route uses custom controller
        $supabaseLoginRoute = $routes->getByName('supabase.login');
        $this->assertStringContainsString('AuthController', $supabaseLoginRoute->getActionName());

        // Test main register route uses Breeze controller
        $registerRoute = $routes->getByName('register');
        $this->assertStringContainsString('RegisteredUserController', $registerRoute->getActionName());

        // Test legacy register route uses custom controller
        $supabaseRegisterRoute = $routes->getByName('supabase.register');
        $this->assertStringContainsString('AuthController', $supabaseRegisterRoute->getActionName());
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
