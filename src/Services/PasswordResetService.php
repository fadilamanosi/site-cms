<?php

namespace SiteCms\Services;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use SiteCms\Mail\ResetPasswordMail;
use SiteCms\Support\DeskResolver;
use SiteCms\Support\GuardResolver;

class PasswordResetService
{
    public const SENT_MESSAGE = 'If that email is registered, we sent a reset link.';

    public function __construct(
        private readonly GuardResolver $guardResolver,
        private readonly DeskResolver $deskResolver,
    ) {}

    public function send(string $email): string
    {
        return Password::broker($this->guardResolver->passwordBroker())->sendResetLink(
            ['email' => $email],
            function (CanResetPassword $user, string $token): void {
                Mail::to($user->getEmailForPasswordReset())->send(
                    new ResetPasswordMail(
                        $this->resetUrl($user, $token),
                        $this->guardResolver->passwordExpireMinutes(),
                    ),
                );
            },
        );
    }

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function retryAfterSeconds(string $email): int
    {
        $throttle = $this->guardResolver->passwordThrottleSeconds();
        if ($throttle <= 0 || $email === '') {
            return 0;
        }

        $broker = $this->guardResolver->passwordBroker();
        $table = config("auth.passwords.{$broker}.table", 'password_reset_tokens');
        if (! is_string($table) || $table === '') {
            return 0;
        }

        $createdAt = DB::table($table)->where('email', $email)->value('created_at');
        if (! is_string($createdAt) && ! $createdAt instanceof \DateTimeInterface) {
            return 0;
        }

        $availableAt = Carbon::parse($createdAt)->addSeconds($throttle)->getTimestamp();

        return max(0, $availableAt - time());
    }

    public function reset(array $credentials, callable $callback): string
    {
        return Password::broker($this->guardResolver->passwordBroker())->reset($credentials, $callback);
    }

    public function updatePassword(mixed $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    private function resetUrl(CanResetPassword $user, string $token): string
    {
        $login = $this->deskResolver->loginPath();

        return url($login.'/reset-password/'.$token).'?email='.urlencode($user->getEmailForPasswordReset());
    }
}
