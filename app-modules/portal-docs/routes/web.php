<?php

declare(strict_types=1);

use He4rt\PortalDocs\Http\Controllers\DocsController;
use He4rt\PortalDocs\Http\Middleware\HandlePortalDocsInertiaRequests;
use Illuminate\Support\Facades\Route;

/*
 * internachi/modular only requires this file; it applies no middleware group of its own,
 * so "web" is declared here or Inertia has no session to work with.
 *
 * Order matters: llms*.txt, api and {slug}.md all match the {slug} pattern, so they are
 * registered first.
 */
Route::middleware([
    'web',
    HandlePortalDocsInertiaRequests::class,
    ...config()->array('portal-docs.middleware', []),
])
    ->prefix(config()->string('portal-docs.prefix', 'docs'))
    ->name('portal-docs.')
    ->controller(DocsController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('llms.txt', 'llms')->name('llms');
        Route::get('llms-full.txt', 'llmsFull')->name('llms-full');
        Route::get('api', 'api')->name('api');
        Route::get('{slug}.md', 'markdown')->where('slug', '[A-Za-z0-9_\-/]+')->name('markdown');
        Route::get('{slug}', 'show')->where('slug', '[A-Za-z0-9_\-/]+')->name('page');
    });
