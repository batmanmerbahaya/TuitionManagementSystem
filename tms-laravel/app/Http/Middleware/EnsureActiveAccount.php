<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every web request of a logged-in user:
 *  - if the account was deleted meanwhile (by an admin, or from another browser), log out now;
 *  - re-read an admin's role, so promoting/demoting an admin takes effect on their next click.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $session = $request->session();

        if ($user->statusflag === 'D') {
            return $this->signOut($request, 'deleted');
        }

        if ($session->get('userRole') === 'ADMIN') {
            $adminType = Admin::where('userid', $user->userid)->value('adminrole');
            if ($adminType === null) {
                return $this->signOut($request, 'invalid');
            }
            $session->put('adminType', $adminType);
        }

        return $next($request);
    }

    private function signOut(Request $request, string $status): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response('Your session has ended. Please log in again.', 401);
        }

        return redirect()->route('login', ['status' => $status]);
    }
}
