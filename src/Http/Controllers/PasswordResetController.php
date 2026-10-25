<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Http\Requests\ResetPasswordRequest;
use SiteCms\Http\Requests\SendResetLinkRequest;
use SiteCms\Services\PasswordResetService;
use SiteCms\Support\DeskResolver;
use SiteCms\Support\GuardResolver;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
        private readonly GuardResolver $guardResolver,
        private readonly DeskResolver $deskResolver,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('SiteCms/ForgotPassword', [
            'status' => $request->session()->get('status'),
            'retryUntil' => $this->retryUntil($request),
            'seo' => [
                'title' => 'Forgot password',
                'description' => 'Site CMS',
                'image' => '',
            ],
        ]);
    }

    public function store(SendResetLinkRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $status = $this->passwordReset->send($email);
        $seconds = $status === Password::RESET_THROTTLED
            ? max(1, $this->passwordReset->retryAfterSeconds($email))
            : $this->guardResolver->passwordThrottleSeconds();

        if ($seconds > 0) {
            $request->session()->put('siteCmsPasswordRetryUntil', time() + $seconds);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back();
        }

        return back()->with('status', PasswordResetService::SENT_MESSAGE);
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('SiteCms/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'seo' => [
                'title' => 'Reset password',
                'description' => 'Site CMS',
                'image' => '',
            ],
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $guard = $this->guardResolver->name();
        $status = $this->passwordReset->reset(
            $request->validated(),
            function (mixed $user, string $password) use ($guard): void {
                $this->passwordReset->updatePassword($user, $password);
                auth($guard)->login($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'This reset link is invalid or has expired.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to(url($this->deskResolver->cmsPath()));
    }

    private function retryUntil(Request $request): ?int
    {
        $until = (int) $request->session()->get('siteCmsPasswordRetryUntil', 0);
        if ($until <= time()) {
            $request->session()->forget('siteCmsPasswordRetryUntil');

            return null;
        }

        return $until;
    }
}
