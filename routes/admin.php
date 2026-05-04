<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AutomationRuleController;
use App\Http\Controllers\Admin\CannedResponseController;
use App\Http\Controllers\Admin\CommandCenterController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CsatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\IssueTrackingController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SupportDepartmentController;
use App\Http\Controllers\Admin\TicketBulkController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TicketMergeSplitController;
use App\Http\Controllers\Admin\TicketSavedViewController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active.user', 'provider.user', 'tenant'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.home'))->name('dashboard');
        Route::get('search', SearchController::class)->name('search');
        Route::get('command-center', CommandCenterController::class)->name('command-center.index');

        Route::patch('tickets/bulk', TicketBulkController::class)->name('tickets.bulk');
        Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store']);
        Route::scopeBindings()->group(function (): void {
            Route::get('companies/{company:slug}/tickets/{ticket:ticket_number}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
            Route::patch('companies/{company:slug}/tickets/{ticket:ticket_number}', [TicketController::class, 'update'])->whereNumber('ticket')->name('tickets.update');
            Route::patch('companies/{company:slug}/tickets/{ticket:ticket_number}/status', [TicketController::class, 'changeStatus'])->whereNumber('ticket')->name('tickets.status');
            Route::patch('companies/{company:slug}/tickets/{ticket:ticket_number}/assignment', [TicketController::class, 'assign'])->whereNumber('ticket')->name('tickets.assignment');
            Route::patch('companies/{company:slug}/tickets/{ticket:ticket_number}/targets', [TicketController::class, 'updateTargets'])->whereNumber('ticket')->name('tickets.targets');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/merge', [TicketMergeSplitController::class, 'merge'])->whereNumber('ticket')->name('tickets.merge');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/split', [TicketMergeSplitController::class, 'split'])->whereNumber('ticket')->name('tickets.split');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/watchers', [TicketController::class, 'addWatcher'])->whereNumber('ticket')->name('tickets.watchers.store');
            Route::delete('companies/{company:slug}/tickets/{ticket:ticket_number}/watchers/{user}', [TicketController::class, 'removeWatcher'])->whereNumber('ticket')->name('tickets.watchers.destroy');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/attachments', [TicketController::class, 'attachment'])->whereNumber('ticket')->name('tickets.attachments.store');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/comments', [TicketController::class, 'comment'])->whereNumber('ticket')->name('tickets.comments.store');
            Route::post('companies/{company:slug}/tickets/{ticket:ticket_number}/csat/resend', [CsatController::class, 'resend'])->whereNumber('ticket')->name('tickets.csat.resend');
        });
        Route::get('tickets/{ticket}', [TicketController::class, 'legacyShow'])->name('tickets.legacy-show');
        Route::post('ticket-views', [TicketSavedViewController::class, 'store'])->name('ticket-views.store');
        Route::patch('ticket-views/{ticketView}', [TicketSavedViewController::class, 'update'])->name('ticket-views.update');
        Route::delete('ticket-views/{ticketView}', [TicketSavedViewController::class, 'destroy'])->name('ticket-views.destroy');

        Route::get('knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
        Route::post('knowledge-base/categories', [KnowledgeBaseController::class, 'storeCategory'])->name('knowledge-base.categories.store');
        Route::patch('knowledge-base/categories/{category}', [KnowledgeBaseController::class, 'updateCategory'])->name('knowledge-base.categories.update');
        Route::delete('knowledge-base/categories/{category}', [KnowledgeBaseController::class, 'destroyCategory'])->name('knowledge-base.categories.destroy');
        Route::post('knowledge-base/articles', [KnowledgeBaseController::class, 'storeArticle'])->name('knowledge-base.articles.store');
        Route::patch('knowledge-base/articles/{article}', [KnowledgeBaseController::class, 'updateArticle'])->name('knowledge-base.articles.update');
        Route::delete('knowledge-base/articles/{article}', [KnowledgeBaseController::class, 'destroyArticle'])->name('knowledge-base.articles.destroy');

        Route::resource('canned-responses', CannedResponseController::class)
            ->parameters(['canned-responses' => 'cannedResponse'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/exports', [ReportController::class, 'storeExport'])->name('reports.exports.store');
        Route::get('reports/exports/{reportExport}/download', [ReportController::class, 'download'])->name('reports.exports.download');
        Route::get('reports/tickets.csv', [ReportController::class, 'ticketsCsv'])->name('reports.tickets.csv');
        Route::get('reports/tickets.pdf', [ReportController::class, 'ticketsPdf'])->name('reports.tickets.pdf');
        Route::get('reports/csat.csv', [ReportController::class, 'csatCsv'])->name('reports.csat.csv');
        Route::get('reports/csat.pdf', [ReportController::class, 'csatPdf'])->name('reports.csat.pdf');

        Route::resource('departments', SupportDepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('issue-tracking', [IssueTrackingController::class, 'index'])->name('issue-tracking.index');
        Route::post('issue-tracking/projects', [IssueTrackingController::class, 'storeProject'])->name('issue-tracking.projects.store');
        Route::patch('issue-tracking/projects/{project}', [IssueTrackingController::class, 'updateProject'])->name('issue-tracking.projects.update');
        Route::post('issue-tracking/trackers', [IssueTrackingController::class, 'storeTracker'])->name('issue-tracking.trackers.store');
        Route::patch('issue-tracking/trackers/{tracker}', [IssueTrackingController::class, 'updateTracker'])->name('issue-tracking.trackers.update');
        Route::post('issue-tracking/categories', [IssueTrackingController::class, 'storeCategory'])->name('issue-tracking.categories.store');
        Route::patch('issue-tracking/categories/{category}', [IssueTrackingController::class, 'updateCategory'])->name('issue-tracking.categories.update');
        Route::post('issue-tracking/tags', [IssueTrackingController::class, 'storeTag'])->name('issue-tracking.tags.store');
        Route::patch('issue-tracking/tags/{tag}', [IssueTrackingController::class, 'updateTag'])->name('issue-tracking.tags.update');
        Route::delete('issue-tracking/tags/{tag}', [IssueTrackingController::class, 'destroyTag'])->name('issue-tracking.tags.destroy');
        Route::post('issue-tracking/custom-fields', [IssueTrackingController::class, 'storeCustomField'])->name('issue-tracking.custom-fields.store');
        Route::patch('issue-tracking/custom-fields/{customField}', [IssueTrackingController::class, 'updateCustomField'])->name('issue-tracking.custom-fields.update');
        Route::delete('issue-tracking/custom-fields/{customField}', [IssueTrackingController::class, 'destroyCustomField'])->name('issue-tracking.custom-fields.destroy');
        Route::resource('companies', CompanyController::class)->only(['index', 'store', 'show']);
        Route::patch('companies/{company}/branding', [CompanyController::class, 'updateBranding'])->name('companies.branding.update');
        Route::patch('companies/{company}/api-docs-access', [CompanyController::class, 'updateApiDocsAccess'])->name('companies.api-docs-access.update');
        Route::patch('companies/{company}/sla-policies/{policy}', [CompanyController::class, 'updateSlaPolicy'])->name('companies.sla-policies.update');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::patch('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::delete('invitations/{invitation}', [InvitationController::class, 'revoke'])->name('invitations.revoke');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs.csv', [AuditLogController::class, 'csv'])->name('audit-logs.csv');
        Route::resource('automation-rules', AutomationRuleController::class)
            ->parameters(['automation-rules' => 'automationRule'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('automation-rules/preview', [AutomationRuleController::class, 'preview'])->name('automation-rules.preview');

        Route::get('dashboard', DashboardController::class)->name('home');
    });
