<?php

use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EditController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'cache.headers:no_store;private'])->group(function () {
    Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'store'])
        ->middleware('throttle:5,1,admin-login')
        ->name('login.store');
});

Route::middleware(['auth', 'cache.headers:no_store;private'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
    Route::get('/edits', [EditController::class, 'page'])->name('edits.page');
    Route::get('/edits/{edit}/attachments', [AttachmentController::class, 'index'])
        ->name('edit.attachments.index');
    Route::post('/edits/{edit}/attachments', [AttachmentController::class, 'store'])
        ->name('edit.attachments.store');

    Route::get('/api/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/api/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/api/clients/{client}', [ClientController::class, 'show'])->whereNumber('client')->name('clients.show');
    Route::patch('/api/clients/{client}', [ClientController::class, 'update'])->whereNumber('client')->name('clients.update');
    Route::delete('/api/clients/{client}', [ClientController::class, 'destroy'])->whereNumber('client')->name('clients.destroy');

    Route::get('/api/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/api/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/api/companies/{company}', [CompanyController::class, 'show'])->whereNumber('company')->name('companies.show');
    Route::patch('/api/companies/{company}', [CompanyController::class, 'update'])->whereNumber('company')->name('companies.update');
    Route::delete('/api/companies/{company}', [CompanyController::class, 'destroy'])->whereNumber('company')->name('companies.destroy');

    Route::get('/api/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/api/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/api/projects/{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('projects.show');
    Route::patch('/api/projects/{project}', [ProjectController::class, 'update'])->whereNumber('project')->name('projects.update');
    Route::delete('/api/projects/{project}', [ProjectController::class, 'destroy'])->whereNumber('project')->name('projects.destroy');

    Route::get('/api/edits', [EditController::class, 'index'])->name('edits.index');
    Route::post('/api/edits', [EditController::class, 'store'])->name('edits.store');
    Route::get('/api/edits/{edit}', [EditController::class, 'show'])->whereNumber('edit')->name('edits.show');
    Route::patch('/api/edits/{edit}', [EditController::class, 'update'])->whereNumber('edit')->name('edits.update');
    Route::delete('/api/edits/{edit}', [EditController::class, 'destroy'])->whereNumber('edit')->name('edits.destroy');

    Route::prefix('/api/edits/{edit}/attachments')
        ->name('edits.attachments.')
        ->where(['edit' => '[0-9]+', 'attachment' => '[0-9]+'])
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [AttachmentController::class, 'listForEdit'])->name('index');
            Route::post('/', [AttachmentController::class, 'upload'])->name('store');
            Route::get('/{attachment}', [AttachmentController::class, 'show'])->name('show');
            Route::get('/{attachment}/preview', [AttachmentController::class, 'preview'])->name('preview');
            Route::get('/{attachment}/download', [AttachmentController::class, 'download'])->name('download');
            Route::delete('/{attachment}', [AttachmentController::class, 'destroy'])->name('destroy');
        });
});
