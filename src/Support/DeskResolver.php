<?php

namespace SiteCms\Support;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DeskResolver
{
    /**
     * @var array{
     *     embedded: bool,
     *     path: string,
     *     login: string,
     *     guard: string,
     *     cmsPath: string
     * }|null
     */
    private ?array $resolved = null;

    public function embedded(): bool
    {
        return $this->resolve()['embedded'];
    }

    public function guard(): string
    {
        return $this->resolve()['guard'];
    }

    public function loginPath(): string
    {
        return $this->resolve()['login'];
    }

    public function cmsPath(): string
    {
        return $this->resolve()['cmsPath'];
    }

    public function adminPath(): string
    {
        return $this->resolve()['path'];
    }

    /**
     * @return array{embedded: bool, path: string, login: string, guard: string, cmsPath: string}
     */
    public function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $ui = strtolower((string) config('site-cms.ui', 'auto'));
        if (! in_array($ui, ['auto', 'embedded', 'standalone'], true)) {
            $ui = 'auto';
        }

        $hint = $this->configuredCandidate();
        $desk = null;

        if ($ui !== 'standalone') {
            $candidates = [];
            if ($hint !== null) {
                $candidates[] = $hint;
            }
            foreach ($this->defaultCandidates() as $candidate) {
                $candidates[] = $candidate;
            }

            foreach ($candidates as $candidate) {
                $desk = $this->probe($candidate);
                if ($desk !== null) {
                    break;
                }
            }

            if ($desk === null && ($ui === 'embedded' || $hint !== null)) {
                Log::warning('site-cms: admin desk did not pass the authed-dashboard probe; using standalone UI.', [
                    'admin_path' => $hint['path'] ?? null,
                    'guard' => $hint['guard'] ?? null,
                    'login' => $hint['login'] ?? null,
                ]);
            }
        }

        if ($desk !== null) {
            return $this->resolved = [
                'embedded' => true,
                'path' => $desk['path'],
                'login' => $desk['login'],
                'guard' => $desk['guard'],
                'cmsPath' => $this->configuredCmsPath() ?? $this->embeddedCmsPath($desk['path']),
            ];
        }

        return $this->resolved = [
            'embedded' => false,
            'path' => $hint['path'] ?? 'cms',
            'login' => $this->configuredLogin() ?? 'cms/login',
            'guard' => $this->standaloneGuard($hint['guard'] ?? null),
            'cmsPath' => $this->configuredCmsPath() ?? 'cms',
        ];
    }

    public function shouldRedirectLegacyStaffPaths(): bool
    {
        if ($this->embedded()) {
            return false;
        }
        if ($this->loginPath() === 'staff/login' || str_starts_with($this->cmsPath(), 'staff')) {
            return false;
        }
        if ($this->hasGet('staff/login') || $this->hasGet('staff/site')) {
            return false;
        }

        return true;
    }

    public function hasGet(string $uri): bool
    {
        return $this->getRouteByUri($uri, 'GET') !== null;
    }

    /**
     * @return array{path: string, guard: ?string, login: ?string}|null
     */
    private function configuredCandidate(): ?array
    {
        $path = trim((string) config('site-cms.admin_path', ''), '/');
        $guard = config('site-cms.guard');
        $guard = is_string($guard) && $guard !== '' ? $guard : null;
        $login = $this->configuredLogin();

        if ($path === '' && $guard === null) {
            return null;
        }

        if ($path === '' && $guard !== null) {
            $path = $guard;
        }

        return [
            'path' => $path,
            'guard' => $guard,
            'login' => $login,
        ];
    }

    /**
     * @return list<array{path: string, guard: string, login: null}>
     */
    private function defaultCandidates(): array
    {
        return [
            ['path' => 'staff', 'guard' => 'staff', 'login' => null],
            ['path' => 'admin', 'guard' => 'admin', 'login' => null],
            ['path' => 'console', 'guard' => 'admin', 'login' => null],
            ['path' => 'console', 'guard' => 'web', 'login' => null],
        ];
    }

    /**
     * @param  array{path: string, guard: ?string, login: ?string}  $candidate
     * @return array{path: string, guard: string, login: string}|null
     */
    private function probe(array $candidate): ?array
    {
        $path = trim((string) $candidate['path'], '/');
        if ($path === '') {
            return null;
        }

        $guard = $candidate['guard'];
        if (! is_string($guard) || $guard === '') {
            $guard = $this->guardOnPrefix($path);
        }

        if (! is_string($guard) || ! $this->guardExists($guard) || ! $this->userModelExists($guard)) {
            return null;
        }

        if (! $this->prefixHasAuth($path, $guard)) {
            return null;
        }

        $login = $this->findLogin($path, $guard, $candidate['login'] ?? null);
        if ($login === null) {
            return null;
        }

        return [
            'path' => $path,
            'guard' => $guard,
            'login' => $login,
        ];
    }

    private function configuredLogin(): ?string
    {
        $login = config('site-cms.login_path');

        return is_string($login) && $login !== '' ? trim($login, '/') : null;
    }

    private function configuredCmsPath(): ?string
    {
        $configured = trim((string) config('site-cms.path', ''), '/');

        return $configured !== '' ? $configured : null;
    }

    private function embeddedCmsPath(string $adminPath): string
    {
        $adminPath = trim($adminPath, '/');

        return ($adminPath !== '' ? $adminPath : 'staff').'/site';
    }

    private function standaloneGuard(?string $configured): string
    {
        if (is_string($configured) && $this->guardExists($configured)) {
            return $configured;
        }

        $guards = array_keys(config('auth.guards', []));
        if (in_array('staff', $guards, true)) {
            return 'staff';
        }

        return (string) config('auth.defaults.guard', 'web');
    }

    private function guardExists(string $guard): bool
    {
        return is_array(config('auth.guards.'.$guard));
    }

    private function userModelExists(string $guard): bool
    {
        $provider = config("auth.guards.{$guard}.provider");
        $model = config("auth.providers.{$provider}.model");

        return is_string($model) && $model !== '' && class_exists($model);
    }

    private function prefixHasAuth(string $path, string $guard): bool
    {
        foreach (Route::getRoutes() as $route) {
            if (! $this->routeInPrefix($route, $path)) {
                continue;
            }
            if ($this->routeUsesGuard($route, $guard)) {
                return true;
            }
        }

        return false;
    }

    private function guardOnPrefix(string $path): ?string
    {
        foreach (Route::getRoutes() as $route) {
            if (! $this->routeInPrefix($route, $path)) {
                continue;
            }
            $guard = $this->guardFromMiddleware($route);
            if ($guard !== null && $this->guardExists($guard)) {
                return $guard;
            }
        }

        return $this->guardExists($path) ? $path : null;
    }

    private function findLogin(string $path, string $guard, ?string $configured): ?string
    {
        $candidates = [];
        if (is_string($configured) && $configured !== '') {
            $candidates[] = trim($configured, '/');
        }
        $candidates[] = $path.'/login';
        $candidates[] = $path;

        foreach (array_unique($candidates) as $uri) {
            $route = $this->getRouteByUri($uri, 'GET');
            if ($route === null) {
                continue;
            }
            if ($this->routeUsesGuard($route, $guard)) {
                continue;
            }

            return $uri;
        }

        foreach ([$path.'.login', 'staff.login', 'admin.login', 'login'] as $name) {
            if (! Route::has($name)) {
                continue;
            }
            $route = Route::getRoutes()->getByName($name);
            if (! $route instanceof RoutingRoute) {
                continue;
            }
            if ($this->routeUsesGuard($route, $guard)) {
                continue;
            }
            $uri = trim($route->uri(), '/');
            if ($uri === '') {
                continue;
            }

            return $uri;
        }

        return null;
    }

    private function routeInPrefix(RoutingRoute $route, string $path): bool
    {
        $uri = trim($route->uri(), '/');

        return $uri === $path || str_starts_with($uri, $path.'/');
    }

    private function routeUsesGuard(RoutingRoute $route, string $guard): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            if ($this->middlewareIsGuard($middleware, $guard)) {
                return true;
            }
        }

        return false;
    }

    private function guardFromMiddleware(RoutingRoute $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            if (preg_match('/(?:^auth:|(?:\\\\|\/)Authenticate:)([a-zA-Z0-9_-]+)/', $middleware, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    private function middlewareIsGuard(string $middleware, string $guard): bool
    {
        if ($middleware === 'auth:'.$guard || str_starts_with($middleware, 'auth:'.$guard.',')) {
            return true;
        }

        if (preg_match('/(?:^auth:|(?:\\\\|\/)Authenticate:)'.$guard.'(?:\||,|$)/', $middleware) === 1) {
            return true;
        }

        return false;
    }

    private function getRouteByUri(string $uri, string $method): ?RoutingRoute
    {
        $uri = trim($uri, '/');
        foreach (Route::getRoutes() as $route) {
            if (trim($route->uri(), '/') !== $uri) {
                continue;
            }
            if (! in_array($method, $route->methods(), true)) {
                continue;
            }

            return $route;
        }

        return null;
    }
}
