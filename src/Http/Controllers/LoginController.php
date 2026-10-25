<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Http\Requests\LoginRequest;
use SiteCms\Support\DeskResolver;
use SiteCms\Support\GuardResolver;

class LoginController extends Controller
{
    public function __construct(
        private readonly GuardResolver $guardResolver,
        private readonly DeskResolver $deskResolver,
    ) {}

    public function create(): Response
    {
        return Inertia::render('SiteCms/Login', [
            'seo' => [
                'title' => 'Sign in',
                'description' => 'Site CMS',
                'image' => '',
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $guard = $this->guardResolver->name();
        $remember = false;

        if (! auth($guard)->attempt($request->only('email', 'password'), $remember)) {
            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(url($this->deskResolver->cmsPath()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $guard = $this->guardResolver->name();
        auth($guard)->logout();
        $request->session()->forget('site_cms.editing');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(url($this->deskResolver->loginPath()));
    }
}
