<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every portal route.
 *
 * Local development is always open, so a fresh clone can read the docs without seeding
 * permissions. Everywhere else the ability named by `portal-docs.gate` decides; setting
 * that config to null publishes the portal to everyone, which is a deliberate choice and
 * not the default.
 */
final class AuthorizeDocsAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $ability = config()->string('portal-docs.gate');

        if ($ability === '') {
            return $next($request);
        }

        abort_unless(Gate::allows($ability), 403);

        return $next($request);
    }
}
