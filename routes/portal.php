<?php

use App\Http\Controllers\Portal\ApiTokenController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\KnowledgeBaseController;
use App\Http\Controllers\Portal\ReportController;
use App\Http\Controllers\Portal\SearchController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\TicketSavedViewController;
use App\Http\Controllers\Portal\UserController;
use App\Http\Controllers\Portal\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active.user', 'client.user', 'tenant'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('portal.home'))->name('dashboard');
        Route::get('dashboard', DashboardController::class)->name('home');
        Route::get('search', SearchController::class)->name('search');

        Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('tickets/{ticket:ticket_number}/status', [TicketController::class, 'changeStatus'])->whereNumber('ticket')->name('tickets.status');
        Route::post('tickets/{ticket:ticket_number}/watchers', [TicketController::class, 'addWatcher'])->whereNumber('ticket')->name('tickets.watchers.store');
        Route::delete('tickets/{ticket:ticket_number}/watchers/{user}', [TicketController::class, 'removeWatcher'])->whereNumber('ticket')->name('tickets.watchers.destroy');
        Route::post('tickets/{ticket:ticket_number}/attachments', [TicketController::class, 'attachment'])->whereNumber('ticket')->name('tickets.attachments.store');
        Route::post('tickets/{ticket:ticket_number}/comments', [TicketController::class, 'comment'])->whereNumber('ticket')->name('tickets.comments.store');
        Route::post('ticket-views', [TicketSavedViewController::class, 'store'])->name('ticket-views.store');
        Route::patch('ticket-views/{ticketView}', [TicketSavedViewController::class, 'update'])->name('ticket-views.update');
        Route::delete('ticket-views/{ticketView}', [TicketSavedViewController::class, 'destroy'])->name('ticket-views.destroy');

        Route::get('knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
        Route::get('knowledge-base/{slug}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
        Route::post('knowledge-base/{slug}/feedback', [KnowledgeBaseController::class, 'feedback'])->name('knowledge-base.feedback');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/exports', [ReportController::class, 'storeExport'])->name('reports.exports.store');
        Route::get('reports/exports/{reportExport}/download', [ReportController::class, 'download'])->name('reports.exports.download');
        Route::get('reports/tickets.csv', [ReportController::class, 'ticketsCsv'])->name('reports.tickets.csv');
        Route::get('reports/tickets.pdf', [ReportController::class, 'ticketsPdf'])->name('reports.tickets.pdf');
        Route::get('reports/csat.csv', [ReportController::class, 'csatCsv'])->name('reports.csat.csv');
        Route::get('reports/csat.pdf', [ReportController::class, 'csatPdf'])->name('reports.csat.pdf');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/invitations', [UserController::class, 'invite'])->name('users.invitations.store');
        Route::patch('users/invitations/{invitation}/resend', [UserController::class, 'resendInvitation'])->name('users.invitations.resend');
        Route::delete('users/invitations/{invitation}', [UserController::class, 'revokeInvitation'])->name('users.invitations.revoke');

        Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('api-tokens/{apiClient}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

        Route::get('webhooks', [WebhookEndpointController::class, 'index'])->name('webhooks.index');
        Route::post('webhooks', [WebhookEndpointController::class, 'store'])->name('webhooks.store');
        Route::post('webhooks/{webhookEndpoint}/test', [WebhookEndpointController::class, 'test'])->name('webhooks.test');
        Route::patch('webhooks/{webhookEndpoint}/secret', [WebhookEndpointController::class, 'rotateSecret'])->name('webhooks.secret');
        Route::post('webhooks/{webhookEndpoint}/deliveries/{delivery}/retry', [WebhookEndpointController::class, 'retry'])->name('webhooks.deliveries.retry');
        Route::delete('webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy'])->name('webhooks.destroy');
    });
