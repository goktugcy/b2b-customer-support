<?php

use App\Http\Controllers\Api\V1\CsatController;
use App\Http\Controllers\Api\V1\KnowledgeBaseController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TicketBulkController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketSavedViewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::post('csat/{token}', [CsatController::class, 'submit'])
            ->middleware('throttle:api')
            ->name('csat.submit');

        Route::middleware(['auth:sanctum', 'active.api_client', 'tenant', 'throttle:api'])
            ->group(function (): void {
                Route::get('meta/ticket-options', [MetaController::class, 'ticketOptions'])->name('meta.ticket-options');

                Route::get('knowledge-base/categories', [KnowledgeBaseController::class, 'categories'])->name('knowledge-base.categories.index');
                Route::get('knowledge-base/articles', [KnowledgeBaseController::class, 'articles'])->name('knowledge-base.articles.index');
                Route::get('knowledge-base/articles/{slug}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.articles.show');

                Route::apiResource('ticket-views', TicketSavedViewController::class)
                    ->parameters(['ticket-views' => 'ticketView'])
                    ->only(['index', 'store', 'update', 'destroy']);

                Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
                Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
                Route::patch('tickets/bulk', TicketBulkController::class)->name('tickets.bulk');
                Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
                Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');
                Route::post('tickets/{ticket}/attachments', [TicketController::class, 'attachment'])->name('tickets.attachments.store');

                Route::get('reports/tickets.csv', [ReportController::class, 'ticketsCsv'])->name('reports.tickets.csv');
                Route::get('reports/tickets.pdf', [ReportController::class, 'ticketsPdf'])->name('reports.tickets.pdf');
                Route::get('reports/csat.csv', [ReportController::class, 'csatCsv'])->name('reports.csat.csv');
                Route::get('reports/csat.pdf', [ReportController::class, 'csatPdf'])->name('reports.csat.pdf');
            });
    });
