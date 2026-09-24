<?php

declare(strict_types=1);

use He4rt\PortalDocs\Http\Middleware\AuthorizeDocsAccess;

return [
    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Every portal route hangs off this prefix. Changing it moves the whole
    | portal, including the LLM endpoints and the raw markdown responses.
    |
    */

    'prefix' => env('PORTAL_DOCS_PREFIX', 'docs'),

    /*
    |--------------------------------------------------------------------------
    | Content Path
    |--------------------------------------------------------------------------
    |
    | Directory holding docs.json and the .mdx pages. Tests point this at a
    | fixture so the suite never depends on the shipped content.
    |
    */

    'content_path' => base_path('app-modules/portal-docs/content'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Applied to every portal route on top of the "web" group. Inertia needs
    | session and cookies, so "web" is always present.
    |
    */

    'middleware' => [AuthorizeDocsAccess::class],

    /*
    |--------------------------------------------------------------------------
    | Access Gate
    |--------------------------------------------------------------------------
    |
    | Ability checked by AuthorizeDocsAccess outside the local environment.
    | Null opens the portal to everyone.
    |
    */

    'gate' => env('PORTAL_DOCS_GATE', 'viewDocs'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | "version" replaces the mtime scan in production: set it to a deploy hash
    | so no request has to glob the content directory.
    |
    */

    'cache' => [
        'enabled' => env('PORTAL_DOCS_CACHE', default: true),
        'ttl' => 86_400,
        // Empty, never null: config()->string() rejects null even with a default,
        // because the key exists and only a missing key falls back.
        'version' => env('PORTAL_DOCS_VERSION', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAPI
    |--------------------------------------------------------------------------
    |
    | "api" names which Scramble API the spec loader generates. It matches the
    | name given to Scramble::registerApi(), or "default".
    |
    */

    'openapi' => [
        'enabled' => true,
        'api' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contextual Menu
    |--------------------------------------------------------------------------
    |
    | Entries of the "Copy page" dropdown. Supported: copy, view, chatgpt,
    | claude. docs.json may narrow this list per site.
    |
    */

    'contextual' => ['copy', 'view', 'chatgpt', 'claude'],
];
