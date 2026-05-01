<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\IssueTrackingController;
use App\Http\Controllers\Admin\SupportDepartmentController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'active.user', 'provider.user', 'tenant'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.tickets.index'))->name('dashboard');

        Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show', 'update']);
        Route::patch('tickets/{ticket}/status', [TicketController::class, 'changeStatus'])->name('tickets.status');
        Route::patch('tickets/{ticket}/assignment', [TicketController::class, 'assign'])->name('tickets.assignment');
        Route::patch('tickets/{ticket}/targets', [TicketController::class, 'updateTargets'])->name('tickets.targets');
        Route::post('tickets/{ticket}/watchers', [TicketController::class, 'addWatcher'])->name('tickets.watchers.store');
        Route::delete('tickets/{ticket}/watchers/{user}', [TicketController::class, 'removeWatcher'])->name('tickets.watchers.destroy');
        Route::post('tickets/{ticket}/attachments', [TicketController::class, 'attachment'])->name('tickets.attachments.store');
        Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');

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
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('dashboard', fn () => Inertia::render('Admin/Dashboard'))->name('home');
    });
