<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces AuthUtil.requireRole(): usage ->middleware('role:ADMIN') or 'role:ADMIN,STUDENT'.
 * Anyone without one of the roles is sent to the login page with ?status=invalid.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check() || ! in_array($request->session()->get('userRole'), $roles, true)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response('error', 403);
            }

            return redirect()->route('login', ['status' => 'invalid']);
        }

        return $next($request);
    }
}
