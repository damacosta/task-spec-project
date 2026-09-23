<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Http\Controllers\IdentityOAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('identity')
    ->group(function (): void {
        Route::get('{provider}/connect', [IdentityOAuthController::class, 'connect'])
            ->name('identity.oauth.connect');
        Route::get('{provider}/callback', [IdentityOAuthController::class, 'callback'])
            ->name('identity.oauth.callback');
    });
