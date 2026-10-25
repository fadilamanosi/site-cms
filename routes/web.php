<?php

use Illuminate\Support\Facades\Route;
use SiteCms\Http\Controllers\ContentController;
use SiteCms\Http\Controllers\EditorController;
use SiteCms\Http\Controllers\LoginController;
use SiteCms\Http\Controllers\ResourceController;
use SiteCms\Http\Middleware\AuthenticateSiteCms;
use SiteCms\Http\Middleware\RedirectIfSiteCmsAuthenticated;

$login = trim((string) config('site-cms.login_path', 'staff/login'), '/');
$path = trim((string) config('site-cms.path', 'staff/site'), '/');

Route::middleware('web')->group(function () use ($login, $path): void {
    Route::middleware(RedirectIfSiteCmsAuthenticated::class)->group(function () use ($login): void {
        Route::get($login, [LoginController::class, 'create'])->name('site-cms.login');
        Route::post($login, [LoginController::class, 'store'])->name('site-cms.login.store');
    });

    Route::post($login.'/logout', [LoginController::class, 'destroy'])->name('site-cms.logout');

    Route::middleware(AuthenticateSiteCms::class)->prefix($path)->group(function (): void {
        Route::get('/', [EditorController::class, 'dashboard'])->name('site-cms.dashboard');
        Route::post('/enter', [EditorController::class, 'enter'])->name('site-cms.enter');
        Route::post('/exit', [EditorController::class, 'exit'])->name('site-cms.exit');
        Route::put('/draft', [ContentController::class, 'draft'])->name('site-cms.draft');
        Route::post('/publish', [ContentController::class, 'publish'])->name('site-cms.publish');
        Route::get('/resources', [ResourceController::class, 'index'])->name('site-cms.resources.index');
        Route::post('/resources', [ResourceController::class, 'store'])->name('site-cms.resources.store');
        Route::delete('/resources/{id}', [ResourceController::class, 'destroy'])->name('site-cms.resources.destroy');
    });
});
