<?php

declare(strict_types=1);

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTokenController;
use App\Http\Controllers\EventUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PINCodeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TestGetController;
use App\Http\Controllers\TestPostController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

// Universal API routes - no auth.
Route::prefix(
    'api'
)->middleware([
    'api',
    'universal',
    InitializeTenancyByDomain::class,
])->group(function () {
    Route::get('/test', TestGetController::class); // To be used for debugging purposes. 
    Route::post('/test', TestPostController::class); // To be used for debugging purposes.

    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);
    Route::put('/pincode/{user}', PINCodeController::class); // Route used to update pin code
});

// Universal API routes - auth.
Route::prefix(
    'api'
)->middleware([
    'api',
    'universal',
    InitializeTenancyByDomain::class,
    'auth:sanctum'
])->group(function () {
    //Route::get('/token/refresh', [TokenController::class, 'refresh']);

    Route::get('users', [UserController::class, 'index'])->middleware('ability:*, write');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('ability:*, write');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('ability:*, write');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('ability:*');
});

// Tenant API routes - auth - actions expecting user tokens.
Route::prefix(
    'api'
)->middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'auth:sanctum'
])->group(function () {
    // This route is used to sync the user's role tokens between the server and the client.
    Route::post('/token/sync', [EventTokenController::class, 'sync']);

    // This route is used to seed a new unprivileged user in the database.
    Route::post('users', [UserController::class, 'seed'])->middleware('ability:*, write');
    // This route is to activate or deactivate a user. The user's tokens are revoked upon deactivation.
    Route::post('users/{user}', [UserController::class, 'toggleIsActive'])->middleware('ability:*, write');

    Route::get('events', [EventController::class, 'index'])->middleware('ability:*, write');
    Route::post('events', [EventController::class, 'store'])->middleware('ability:*, write');
    Route::get('events/{event}', [EventController::class, 'show'])->middleware('ability:*, write');
    Route::put('events/{event}', [EventController::class, 'update'])->middleware('ability:*, write');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->middleware('ability:*');

    Route::get('bankaccounts', [BankAccountController::class, 'index'])->middleware('ability:*, write');
    Route::post('bankaccounts', [BankAccountController::class, 'store'])->middleware('ability:*, write');
    Route::get('bankaccounts/{bankaccount}', [BankAccountController::class, 'show'])->middleware('ability:*, write');
    Route::put('bankaccounts/{bankaccount}', [BankAccountController::class, 'update'])->middleware('ability:*, write');
    Route::delete('bankaccounts/{bankaccount}', [BankAccountController::class, 'destroy'])->middleware('ability:*');
});

// Tenant API routes - auth - actions expecting event tokens.
Route::prefix(
    'api'
)->middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'auth:sanctum',
])->group(function () {
    // This route should be visited prior to a sync with all the event tokens possessed by the client. 
    Route::post('/token/purge', [EventTokenController::class, 'purge']);

    // There routes are used to attach, update, and detach roles on the pivot table.
    Route::post('events/{event}/users', [EventUserController::class, 'store'])->middleware('ability:*, write');
    Route::put('events/{event}/users/{user}', [EventUserController::class, 'update'])->middleware('ability:*, write');
    Route::delete('events/{event}/users/{user}', [EventUserController::class, 'destroy'])->middleware('ability:*, write'); // Detach is within scope of write.
});
