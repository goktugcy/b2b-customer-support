<?php

use App\Http\Controllers\Portal\ApiTokenController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\UserController;
use App\Http\Controllers\Portal\WebhookEndpointController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'active.user', 'client.user', 'tenant'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('portal.tickets.index'))->name('dashboard');
        Route::get('dashboard', fn () => Inertia::render('Portal/Dashboard'))->name('home');

        Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/invitations', [UserController::class, 'invite'])->name('users.invitations.store');

        Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('api-tokens/{apiClient}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

        Route::get('webhooks', [WebhookEndpointController::class, 'index'])->name('webhooks.index');
        Route::post('webhooks', [WebhookEndpointController::class, 'store'])->name('webhooks.store');
        Route::delete('webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy'])->name('webhooks.destroy');
    });
