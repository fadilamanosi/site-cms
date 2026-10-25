<?php

namespace SiteCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SiteCms\Support\GuardResolver;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSiteCms
{
    public function __construct(
        private readonly GuardResolver $guardResolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->guardResolver->name();
        if (! auth($guard)->check()) {
            return redirect()->guest(url(config('site-cms.login_path')));
        }

        return $next($request);
    }
}
