<?php

declare(strict_types=1);

use App\Http\Api\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->apiResource('teams', TeamController::class);
