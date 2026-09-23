<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Http\Middleware;

use Inertia\Middleware;

/**
 * Inertia middleware scoped to the portal.
 *
 * The host may run its own Inertia middleware one day with rootView 'app'; this one is
 * applied only to the portal routes, so the two never fight over the root view. It also
 * means the host does not have to register Inertia in bootstrap/app.php for the portal
 * to work — without an Inertia middleware in the stack the version is never set and an
 * Inertia visit answers 409 instead of 200.
 */
final class HandlePortalDocsInertiaRequests extends Middleware
{
    protected $rootView = 'portal-docs::app';
}
