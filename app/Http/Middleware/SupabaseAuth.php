<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupabaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! Session::has('supabase_token') || ! Session::has('user_id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
