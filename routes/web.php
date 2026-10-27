<?php

use Illuminate\Support\Facades\Route;
use SiteCms\Http\Controllers\ContentController;
use SiteCms\Http\Controllers\EditorController;
use SiteCms\Http\Controllers\LoginController;
use SiteCms\Http\Controllers\PasswordResetController;
use SiteCms\Http\Controllers\ResourceController;
use SiteCms\Http\Middleware\AuthenticateSiteCms;
use SiteCms\Http\Middleware\RedirectIfSiteCmsAuthenticated;
use SiteCms\Support\DeskResolver;

$desk = app(DeskResolver::class);
$login = $desk->loginPath();
$path = $desk->cmsPath();

Route::middleware('web')->group(function () use ($desk, $login, $path): void {
    Route::get('/site-cms/media/{id}/{name?}', [ResourceController::class, 'show'])->name('site-cms.media.show')->whereNumber('id')->where('name', '[^/]+');

    if (! $desk->embedded()) {
        if (! $desk->hasGet($login)) {
            Route::middleware(RedirectIfSiteCmsAuthenticated::class)->group(function () use ($login): void {
                Route::get($login, [LoginController::class, 'create'])->name('site-cms.login');
                Route::post($login, [LoginController::class, 'store'])->name('site-cms.login.store');
                Route::get($login.'/forgot-password', [PasswordResetController::class, 'create'])->name('site-cms.password.request');
                Route::post($login.'/forgot-password', [PasswordResetController::class, 'store'])->name('site-cms.password.email');
                Route::get($login.'/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('site-cms.password.reset');
                Route::post($login.'/reset-password', [PasswordResetController::class, 'update'])->name('site-cms.password.update');
            });
        }

        Route::post($login.'/logout', [LoginController::class, 'destroy'])->name('site-cms.logout');

        if ($desk->shouldRedirectLegacyStaffPaths()) {
            Route::get('staff', fn () => redirect('/'.$path));
            Route::get('staff/login', fn () => redirect('/'.$login));
            Route::get('staff/login/forgot-password', fn () => redirect('/'.$login.'/forgot-password'));
            Route::get('staff/login/reset-password/{token}', function (string $token) {
                return redirect()->route('site-cms.password.reset', [
                    'token' => $token,
                    ...request()->query(),
                ]);
            });
            Route::get('staff/site', fn () => redirect('/'.$path));
        }
    }

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
