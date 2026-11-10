<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces AuthUtil.requireSuperAdmin(): only admins whose adminrole is 'S'.
 * Normal admins are sent back to their dashboard.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        if (! Auth::check() || $session->get('userRole') !== 'ADMIN') {
            return redirect()->route('login', ['status' => 'invalid']);
        }

        if ($session->get('adminType') !== 'S') {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
