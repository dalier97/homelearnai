<?php

namespace App\Http\Middleware;

use App\Models\Child;
use App\Services\SupabaseClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class ChildAccess
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userId = auth()->id();
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        // Check if accessing child-specific route
        $childId = $request->route('child_id') ?? $request->route('id');

        if ($childId) {
            $child = Child::find((int) $childId);

            if (! $child) {
                abort(404, 'Child not found');
            }

            // Verify the child belongs to the authenticated user
            if ($child->user_id !== $userId) {
                abort(403, 'Access denied');
            }

            // Store child info in request for easy access
            $request->attributes->set('child', $child);

            // Inherit parent's locale for child views
            $this->inheritParentLocale($request, (string) $userId);
        }

        return $next($request);
    }

    /**
     * Ensure child views inherit the parent's language preference
     */
    private function inheritParentLocale(Request $request, string $userId): void
    {
        try {
            $accessToken = Session::get('supabase_token');
            if (! $accessToken) {
                return;
            }

            // Get parent's locale preference from database
            $this->supabase->setUserToken($accessToken);
            $userPrefs = $this->supabase->from('user_preferences')
                ->select('locale')
                ->eq('user_id', $userId)
                ->single();

            if ($userPrefs && ! empty($userPrefs['locale'])) {
                $parentLocale = $userPrefs['locale'];

                // Apply parent's locale to current child session
                Session::put('locale', $parentLocale);
                App::setLocale($parentLocale);

                \Log::debug('Child view inheriting parent locale', [
                    'user_id' => $userId,
                    'parent_locale' => $parentLocale,
                    'child_id' => $request->route('child_id') ?? $request->route('id'),
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the request if locale inheritance fails
            \Log::warning('Failed to inherit parent locale for child view', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
