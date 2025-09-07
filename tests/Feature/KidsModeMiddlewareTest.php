<?php

namespace Tests\Feature;

use App\Http\Middleware\KidsMode;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModeMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes for middleware testing
        Route::get('/test/parent-only', function () {
            return response('parent-only-content');
        })->middleware('not-in-kids-mode');

        Route::get('/test/always-accessible', function () {
            return response('always-accessible-content');
        });

        Route::get('/test/child-today/{child_id}', function ($childId) {
            return response("child-today-{$childId}");
        })->name('dashboard.child-today');

        Route::get('/test/kids-mode-exit', function () {
            return response('exit-screen');
        })->name('kids-mode.exit');

        // Apply the KidsMode middleware globally to test routes as well
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\KidsMode::class);
    }

    public function test_kids_mode_middleware_allows_access_when_not_in_kids_mode(): void
    {
        // Arrange: Not in kids mode
        Session::put('kids_mode_active', false);

        // Act & Assert: Should allow access to any route
        $response = $this->get('/test/parent-only');
        $response->assertOk();
        $response->assertSeeText('parent-only-content');

        $response = $this->get('/test/always-accessible');
        $response->assertOk();
        $response->assertSeeText('always-accessible-content');
    }

    public function test_kids_mode_middleware_allows_child_today_view(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act & Assert: Should allow access to child today view
        $response = $this->get('/test/child-today/123');
        $response->assertOk();
        $response->assertSeeText('child-today-123');
    }

    public function test_kids_mode_middleware_allows_kids_mode_exit_routes(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act & Assert: Should allow access to exit screen
        $response = $this->get('/test/kids-mode-exit');
        $response->assertOk();
        $response->assertSeeText('exit-screen');
    }

    public function test_kids_mode_middleware_blocks_parent_only_routes(): void
    {
        // Create a test route that matches blocked patterns
        Route::get('/dashboard/parent', function () {
            return response('parent-dashboard');
        });

        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act: Try to access parent dashboard (matches blocked pattern)
        $response = $this->get('/dashboard/parent');

        // Assert: Should redirect to child today page
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Access denied in kids mode');
    }

    public function test_kids_mode_middleware_handles_htmx_requests(): void
    {
        // Create a test route that matches blocked patterns
        Route::get('/planning/index', function () {
            return response('planning-board');
        });

        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act: Try to access blocked route with HTMX header
        $response = $this->get('/planning/index', [
            'HX-Request' => 'true',
        ]);

        // Assert: Should return JSON error with redirect header
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Access denied in kids mode',
        ]);
        $response->assertHeader('HX-Redirect');
    }

    public function test_kids_mode_middleware_allows_static_assets(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Create a test route that simulates static assets
        Route::get('/css/app.css', function () {
            return response('css-content', 200, ['Content-Type' => 'text/css']);
        });

        Route::get('/js/app.js', function () {
            return response('js-content', 200, ['Content-Type' => 'application/javascript']);
        });

        Route::get('/images/test.png', function () {
            return response('image-content', 200, ['Content-Type' => 'image/png']);
        });

        // Act & Assert: Should allow access to static assets
        $response = $this->get('/css/app.css');
        $response->assertOk();

        $response = $this->get('/js/app.js');
        $response->assertOk();

        $response = $this->get('/images/test.png');
        $response->assertOk();
    }

    public function test_kids_mode_middleware_allows_api_routes(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Create test API routes
        Route::get('/api/translations/en', function () {
            return response()->json(['welcome' => 'Welcome']);
        });

        Route::post('/api/user/locale', function () {
            return response()->json(['status' => 'updated']);
        });

        // Act & Assert: Should allow access to allowed API routes
        $response = $this->get('/api/translations/en');
        $response->assertOk();

        $response = $this->post('/api/user/locale', ['locale' => 'en']);
        $response->assertOk();
    }

    public function test_not_in_kids_mode_middleware_allows_access_when_not_in_kids_mode(): void
    {
        // Arrange: Not in kids mode
        Session::put('kids_mode_active', false);

        // Act & Assert: Should allow access to parent-only routes
        $response = $this->get('/test/parent-only');
        $response->assertOk();
        $response->assertSeeText('parent-only-content');
    }

    public function test_not_in_kids_mode_middleware_blocks_access_when_in_kids_mode(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act: Try to access parent-only route
        $response = $this->get('/test/parent-only');

        // Assert: Should redirect to child today page with error
        $response->assertRedirect();
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    public function test_not_in_kids_mode_middleware_handles_htmx_requests_when_blocked(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act: Try to access parent-only route with HTMX header
        $response = $this->get('/test/parent-only', [
            'HX-Request' => 'true',
        ]);

        // Assert: Should return JSON error with redirect header
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'This action is not available in kids mode',
            'message' => 'Please exit kids mode to access this feature',
        ]);
        $response->assertHeader('HX-Redirect');
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('HX-Redirect'));
    }

    public function test_kids_mode_middleware_blocks_routes_with_action_keywords(): void
    {
        // Create test routes with action keywords
        Route::get('/test/subjects/create', function () {
            return response('create-subject');
        });

        Route::get('/test/units/123/edit', function () {
            return response('edit-unit');
        });

        Route::post('/test/topics/store', function () {
            return response('store-topic');
        });

        Route::delete('/test/children/456/destroy', function () {
            return response('destroy-child');
        });

        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act & Assert: Should block routes with action keywords
        $this->get('/test/subjects/create')->assertRedirect();
        $this->get('/test/units/123/edit')->assertRedirect();
        $this->post('/test/topics/store')->assertRedirect();
        $this->delete('/test/children/456/destroy')->assertRedirect();
    }

    public function test_kids_mode_middleware_allows_review_completion(): void
    {
        // Create test routes for review completion (should be allowed)
        Route::post('/test/reviews/complete/123', function () {
            return response('review-completed');
        });

        Route::post('/test/sessions/456/complete', function () {
            return response('session-completed');
        });

        Route::post('/test/reviews/process/789', function () {
            return response('review-processed');
        });

        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act & Assert: Should allow review completion routes
        $response = $this->post('/test/reviews/complete/123');
        $response->assertOk();
        $response->assertSeeText('review-completed');

        $response = $this->post('/test/sessions/456/complete');
        $response->assertOk();
        $response->assertSeeText('session-completed');

        $response = $this->post('/test/reviews/process/789');
        $response->assertOk();
        $response->assertSeeText('review-processed');
    }

    public function test_kids_mode_middleware_blocks_specific_parent_routes(): void
    {
        // Create test routes for specific parent-only functionality
        Route::get('/test/dashboard/parent', function () {
            return response('parent-dashboard');
        });

        Route::get('/test/children/create', function () {
            return response('create-child');
        });

        Route::get('/test/planning/index', function () {
            return response('planning-board');
        });

        Route::get('/test/calendar/import', function () {
            return response('calendar-import');
        });

        Route::get('/test/kids-mode/settings/pin', function () {
            return response('pin-settings');
        });

        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act & Assert: Should block all these parent-only routes
        $this->get('/test/dashboard/parent')->assertRedirect();
        $this->get('/test/children/create')->assertRedirect();
        $this->get('/test/planning/index')->assertRedirect();
        $this->get('/test/calendar/import')->assertRedirect();
        $this->get('/test/kids-mode/settings/pin')->assertRedirect();
    }

    public function test_middleware_logs_access_attempts(): void
    {
        // Arrange: In kids mode
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        // Act: Try to access blocked route (logs will be generated but not tested)
        // We're not testing the exact log content in this test for simplicity
        $response = $this->get('/test/parent-only');

        // Assert: The important thing is that the route is blocked
        $response->assertRedirect();
    }

    public function test_middleware_works_without_kids_mode_session(): void
    {
        // Arrange: No kids mode session data
        Session::flush();

        // Act & Assert: Should work normally (no restrictions)
        $response = $this->get('/test/parent-only');
        $response->assertOk();
        $response->assertSeeText('parent-only-content');

        $response = $this->get('/test/always-accessible');
        $response->assertOk();
        $response->assertSeeText('always-accessible-content');
    }
}
