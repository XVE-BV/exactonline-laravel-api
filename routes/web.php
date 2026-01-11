<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth\CallbackController;
use Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth\RedirectToExactController;

/*
|--------------------------------------------------------------------------
| Exact Online OAuth Routes
|--------------------------------------------------------------------------
|
| These routes handle the OAuth flow for Exact Online authentication.
| The routes are registered with the 'web' middleware by default.
|
*/

Route::middleware('web')->prefix('exact')->name('exact.')->group(function () {
    // OAuth routes
    Route::prefix('oauth')->name('oauth.')->group(function () {
        // Redirect to Exact Online for authorization
        Route::get('redirect/{connection?}', RedirectToExactController::class)
            ->name('redirect');

        // Handle OAuth callback from Exact Online
        Route::get('callback', CallbackController::class)->name('callback');
    });
});
