<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'active.user', 'provider.user', 'tenant'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.tickets.index'))->name('dashboard');

        Route::resource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('tickets/{ticket}/status', [TicketController::class, 'changeStatus'])->name('tickets.status');
        Route::patch('tickets/{ticket}/assignment', [TicketController::class, 'assign'])->name('tickets.assignment');
        Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');

        Route::resource('companies', CompanyController::class)->only(['index', 'store', 'show']);
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('dashboard', fn () => Inertia::render('Admin/Dashboard'))->name('home');
    });
