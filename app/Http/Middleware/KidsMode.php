<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class KidsMode
{
    /**
     * Handle an incoming request.
     *
     * When kids mode is active, restrict navigation to only allowed routes.
     * Block access to parent-only features and redirect unauthorized attempts
     * to the child's today page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply restrictions if kids mode is active
        if (! Session::get('kids_mode_active')) {
            return $next($request);
        }

        $childId = Session::get('kids_mode_child_id');
        $currentRoute = $request->route();
        $currentPath = $request->path();
        $routeName = $currentRoute ? $currentRoute->getName() : null;

        // Log kids mode access attempt for debugging
        \Log::info('KidsMode middleware: Route access attempted', [
            'child_id' => $childId,
            'path' => $currentPath,
            'route_name' => $routeName,
            'method' => $request->method(),
            'is_htmx' => $request->header('HX-Request') === 'true',
            'user_agent' => $request->header('User-Agent'),
        ]);

        // Allow these route patterns in kids mode
        $allowedRoutes = [
            // Child's today view
            'dashboard.child-today',

            // Kids mode exit functionality
            'kids-mode.exit',
            'kids-mode.exit.validate',

            // Review system (read-only for child)
            'reviews.index',
            'reviews.session',
            'reviews.show',
            'reviews.process',
            'reviews.complete',

            // Session completion from child view
            'dashboard.sessions.complete',
            'dashboard.reorder-today', // For independence level 2+

            // Locale management (global functionality)
            'locale.update',
            'locale.translations',
            'locale.available',
            'locale.session',
            'locale.user',
        ];

        // Allow API routes for HTMX updates (but block dangerous operations)
        if ($this->isAllowedApiRoute($currentPath)) {
            return $next($request);
        }

        // Allow static assets
        if ($this->isStaticAsset($currentPath)) {
            return $next($request);
        }

        // Check if current route is explicitly allowed
        if ($routeName && in_array($routeName, $allowedRoutes)) {
            return $next($request);
        }

        // Block parent-only routes
        if ($this->isBlockedRoute($currentPath, $routeName)) {
            $redirectUrl = route('dashboard.child-today', ['child_id' => $childId]);

            \Log::warning('KidsMode middleware: Blocked access to parent-only route', [
                'child_id' => $childId,
                'blocked_path' => $currentPath,
                'blocked_route' => $routeName,
                'redirect_to' => $redirectUrl,
                'is_htmx' => $request->header('HX-Request') === 'true',
            ]);

            // Handle HTMX requests differently
            if ($request->header('HX-Request') === 'true') {
                return response()->json([
                    'error' => __('Access denied in kids mode'),
                    'redirect' => $redirectUrl,
                ], 403)->header('HX-Redirect', $redirectUrl);
            }

            return redirect($redirectUrl)->with('error', __('Access denied in kids mode'));
        }

        return $next($request);
    }

    /**
     * Check if the route should be blocked in kids mode
     */
    private function isBlockedRoute(string $path, ?string $routeName): bool
    {
        // Blocked route patterns
        $blockedPatterns = [
            'dashboard/parent',      // Parent dashboard
            'children',             // Children management
            'planning',             // Planning board
            'calendar',             // Calendar management
            'subjects/create',      // Subject creation
            'subjects/*/edit',      // Subject editing
            'kids-mode/settings',   // PIN settings
        ];

        // Blocked route names
        $blockedRoutes = [
            'dashboard',          // Main dashboard route
            'dashboard.parent',
            'dashboard.skip-day',
            'dashboard.move-theme',
            'dashboard.bulk-complete-today',
            'dashboard.move-session-in-week',
            'dashboard.independence-level',
            'children.index',
            'children.create',
            'children.store',
            'children.edit',
            'children.update',
            'children.destroy',
            'planning.index',
            'planning.create-session',
            'planning.sessions.store',
            'calendar.index',
            'calendar.create',
            'calendar.store',
            'calendar.edit',
            'calendar.update',
            'calendar.destroy',
            'calendar.import',
            'calendar.import.preview',
            'calendar.import.file',
            'calendar.import.url',
            'subjects.index',
            'subjects.create',
            'subjects.store',
            'subjects.edit',
            'subjects.update',
            'subjects.destroy',
            'subjects.quick-start.form',
            'subjects.quick-start.store',
            'units.create',
            'units.store',
            'units.edit',
            'units.update',
            'units.destroy',
            'topics.create',
            'topics.store',
            'topics.edit',
            'topics.update',
            'topics.destroy',
            'kids-mode.settings',
            'kids-mode.pin.update',
            'kids-mode.pin.reset',
            'reviews.slots',
            'reviews.slots.store',
            'reviews.slots.update',
            'reviews.slots.destroy',
            'reviews.slots.toggle',
        ];

        // Check route name first
        if ($routeName && in_array($routeName, $blockedRoutes)) {
            return true;
        }

        // Check path patterns
        foreach ($blockedPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        // Block any route with these action keywords (create, edit, update, delete)
        $actionKeywords = ['create', 'edit', 'update', 'delete', 'destroy', 'store'];
        foreach ($actionKeywords as $keyword) {
            if (str_contains($path, '/'.$keyword) || str_contains($path, $keyword.'/')) {
                // Exception: Don't block review completion or session completion
                if (! str_contains($path, 'reviews/complete') &&
                    ! str_contains($path, 'sessions/complete') &&
                    ! str_contains($path, 'process')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the route is an allowed API route for HTMX
     */
    private function isAllowedApiRoute(string $path): bool
    {
        $allowedApiPatterns = [
            'api/translations/',    // Translation loading
            'api/locales',          // Locale information
            'api/user/locale',      // User locale updates
            'api/session/locale',   // Session locale updates
        ];

        foreach ($allowedApiPatterns as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the request is for static assets
     */
    private function isStaticAsset(string $path): bool
    {
        $staticExtensions = [
            '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg',
            '.woff', '.woff2', '.ttf', '.eot', '.ico', '.webp',
        ];

        foreach ($staticExtensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return true;
            }
        }

        // Laravel mix assets and storage
        return str_starts_with($path, 'css/') ||
               str_starts_with($path, 'js/') ||
               str_starts_with($path, 'storage/') ||
               str_starts_with($path, 'build/');
    }
}
