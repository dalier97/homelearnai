<?php

namespace Tests\Unit;

use App\Http\Middleware\KidsMode;
use App\Http\Middleware\NotInKidsMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModeMiddlewareUnitTest extends TestCase
{
    private KidsMode $kidsMiddleware;

    private NotInKidsMode $notInKidsMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kidsMiddleware = new KidsMode;
        $this->notInKidsMiddleware = new NotInKidsMode;
    }

    public function test_kids_mode_middleware_passes_when_not_active(): void
    {
        // Arrange
        Session::put('kids_mode_active', false);
        $request = Request::create('/any-route', 'GET');
        $next = function ($req) {
            return new Response('success');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('success', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_kids_mode_middleware_allows_child_today_route(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/dashboard/child/123/today', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/dashboard/child/{child_id}/today', []);
            $route->name('dashboard.child-today');
            // Set parameters manually
            $route->parameters = ['child_id' => 123];

            return $route;
        });

        $next = function ($req) {
            return new Response('child-today-content');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('child-today-content', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_kids_mode_middleware_allows_exit_routes(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/kids-mode/exit', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/kids-mode/exit', []);
            $route->name('kids-mode.exit');
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('exit-screen');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('exit-screen', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_kids_mode_middleware_blocks_dashboard_parent(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/dashboard/parent', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/dashboard/parent', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('parent-dashboard');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
    }

    public function test_kids_mode_middleware_blocks_planning_routes(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/planning/index', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/planning/index', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('planning-content');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
    }

    public function test_kids_mode_middleware_blocks_children_routes(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/children/create', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/children/create', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('create-child');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
    }

    public function test_kids_mode_middleware_blocks_create_routes(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/subjects/create', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/subjects/create', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('create-subject');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
    }

    public function test_kids_mode_middleware_allows_static_assets(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/css/app.css', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/css/app.css', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('css-content');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('css-content', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_kids_mode_middleware_allows_api_routes(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/api/translations/en', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/api/translations/en', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('{"welcome":"Welcome"}');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('{"welcome":"Welcome"}', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_kids_mode_middleware_handles_htmx_requests(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/planning/index', 'GET');
        $request->headers->set('HX-Request', 'true');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/planning/index', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('planning-content');
        };

        // Act
        $response = $this->kidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Access denied in kids mode', $response->getContent());
        $this->assertTrue($response->headers->has('HX-Redirect'));
    }

    public function test_not_in_kids_mode_middleware_passes_when_not_active(): void
    {
        // Arrange
        Session::put('kids_mode_active', false);
        $request = Request::create('/any-route', 'GET');
        $next = function ($req) {
            return new Response('success');
        };

        // Act
        $response = $this->notInKidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals('success', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_not_in_kids_mode_middleware_blocks_when_active(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/test-route', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/test-route', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('sensitive-content');
        };

        // Act
        $response = $this->notInKidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard/child/123/today', $response->headers->get('Location'));
    }

    public function test_not_in_kids_mode_middleware_handles_htmx_when_blocked(): void
    {
        // Arrange
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 123);

        $request = Request::create('/test-route', 'GET');
        $request->headers->set('HX-Request', 'true');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/test-route', []);
            $route->bind($request);

            return $route;
        });

        $next = function ($req) {
            return new Response('sensitive-content');
        };

        // Act
        $response = $this->notInKidsMiddleware->handle($request, $next);

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('This action is not available in kids mode', $content['error']);
        $this->assertEquals('Please exit kids mode to access this feature', $content['message']);
        $this->assertTrue($response->headers->has('HX-Redirect'));
    }
}
