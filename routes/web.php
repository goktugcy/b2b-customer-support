<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAttachmentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = request()->user();

    return redirect($user?->isProviderUser()
        ? route('admin.tickets.index', absolute: false)
        : route('portal.tickets.index', absolute: false));
})->middleware(['auth', 'verified', 'active.user'])->name('dashboard');

Route::middleware(['auth', 'active.user', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/attachments/{ticketAttachment}/download', [TicketAttachmentController::class, 'download'])->name('attachments.download');
});

require __DIR__.'/admin.php';
require __DIR__.'/portal.php';
require __DIR__.'/auth.php';
