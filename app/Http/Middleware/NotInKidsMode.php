<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class NotInKidsMode
{
    /**
     * Handle an incoming request.
     *
     * Ensures certain actions can only be done when NOT in kids mode.
     * This middleware should be applied to sensitive routes like PIN management,
     * parent dashboard, and administrative functions that should be blocked
     * when kids mode is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If kids mode is active, block access to sensitive routes
        if (Session::get('kids_mode_active')) {
            $childId = Session::get('kids_mode_child_id');
            $currentRoute = $request->route();
            $currentPath = $request->path();
            $routeName = $currentRoute ? $currentRoute->getName() : null;

            \Log::warning('NotInKidsMode middleware: Blocked access to sensitive route', [
                'child_id' => $childId,
                'blocked_path' => $currentPath,
                'blocked_route' => $routeName,
                'method' => $request->method(),
                'is_htmx' => $request->header('HX-Request') === 'true',
                'kids_mode_entered_at' => Session::get('kids_mode_entered_at'),
            ]);

            $redirectUrl = route('dashboard.child-today', ['child_id' => $childId]);

            // Handle HTMX requests differently
            if ($request->header('HX-Request') === 'true') {
                return response()->json([
                    'error' => __('This action is not available in kids mode'),
                    'message' => __('Please exit kids mode to access this feature'),
                    'redirect' => $redirectUrl,
                ], 403)->header('HX-Redirect', $redirectUrl);
            }

            return redirect($redirectUrl)->with('error', __('This action is not available in kids mode. Please exit kids mode to continue.'));
        }

        return $next($request);
    }
}
