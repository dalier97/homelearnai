<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * Redirects users who have already completed onboarding away from the onboarding wizard.
     * This prevents users from getting stuck in the onboarding flow during E2E tests
     * and provides a better user experience.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (auth()->check()) {
            $user = auth()->user();
            $userPrefs = $user->getPreferences();

            // If user has already completed onboarding, redirect to dashboard
            if ($userPrefs->onboarding_completed) {
                \Log::info('RedirectIfOnboardingCompleted: User has already completed onboarding', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'requested_path' => $request->path(),
                    'onboarding_skipped' => $userPrefs->onboarding_skipped,
                ]);

                return redirect()->route('dashboard')
                    ->with('info', __('You have already completed the initial setup.'));
            }
        }

        return $next($request);
    }
}
