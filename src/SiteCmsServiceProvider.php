<?php

namespace SiteCms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use SiteCms\Console\CreateAdminCommand;
use SiteCms\Services\ShareService;

class SiteCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/site-cms.php', 'site-cms');
        $this->app->singleton(ShareService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'site-cms');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/site-cms.php' => config_path('site-cms.php'),
        ], 'site-cms-config');

        Blade::directive('siteCmsHead', function (): string {
            return '<?php echo view(\'site-cms::head\', [\'page\' => $page ?? []])->render(); ?>';
        });

        $pages = dirname(__DIR__).'/resources/js/Pages';
        $paths = config('inertia.pages.paths', []);
        if (! in_array($pages, $paths, true)) {
            $paths[] = $pages;
            config(['inertia.pages.paths' => $paths]);
        }

        Inertia::share('siteCms', function (Request $request): array {
            return $this->app->make(ShareService::class)->payload($request);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminCommand::class,
            ]);
        }
    }
}
