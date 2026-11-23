<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MISE-2: lets a route through only for the listed roles.
 *
 * Usage in routes/web.php:
 *     Route::middleware('role:owner,chef')->group(function () { ... });
 *
 * Anyone else gets "403 Forbidden".
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'Your role does not have access to this page.');
        }

        return $next($request);
    }
}
