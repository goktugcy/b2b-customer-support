<?php

use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'active.api_client', 'tenant', 'throttle:api'])
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('meta/ticket-options', [MetaController::class, 'ticketOptions'])->name('meta.ticket-options');

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');
        Route::post('tickets/{ticket}/attachments', [TicketController::class, 'attachment'])->name('tickets.attachments.store');
    });
