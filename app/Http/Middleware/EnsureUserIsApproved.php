<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure user has approved status.
 *
 * Redirects non-approved users to the pending approval page.
 */
class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isApproved()) {
            if ($request->user()?->isPending()) {
                return redirect()->route('register.pending');
            }

            abort(403, 'Your account is not approved.');
        }

        return $next($request);
    }
}
