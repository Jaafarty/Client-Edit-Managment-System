<?php

use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'store'])
        ->middleware('throttle:5,1,admin-login')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
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
});
