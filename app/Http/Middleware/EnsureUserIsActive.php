<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of a user whose account has been deactivated (SRS FR14).
 *
 * The login check alone is not enough: someone already signed in, or holding a
 * remember-me cookie, would otherwise keep their access indefinitely after an
 * admin withdrew it. Running on every web request closes that gap on the very
 * next page they load.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Contact an administrator to restore access.',
            ]);
        }

        return $next($request);
    }
}
