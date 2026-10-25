<?php

namespace SiteCms\Support;

class GuardResolver
{
    public function __construct(
        private readonly DeskResolver $deskResolver,
    ) {}

    public function name(): string
    {
        return $this->deskResolver->guard();
    }

    public function userModel(): string
    {
        $configured = config('site-cms.user_model');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $guard = $this->name();
        $provider = config("auth.guards.{$guard}.provider");
        $model = config("auth.providers.{$provider}.model");

        if (is_string($model) && $model !== '') {
            return $model;
        }

        return \App\Models\User::class;
    }

    public function passwordBroker(): string
    {
        $guard = $this->name();
        $provider = config("auth.guards.{$guard}.provider");

        foreach (config('auth.passwords', []) as $name => $config) {
            if (is_array($config) && ($config['provider'] ?? null) === $provider) {
                return (string) $name;
            }
        }

        $default = config('auth.defaults.passwords');

        return is_string($default) && $default !== '' ? $default : 'users';
    }

    public function passwordExpireMinutes(): int
    {
        $broker = $this->passwordBroker();

        return (int) config("auth.passwords.{$broker}.expire", 60);
    }

    public function passwordThrottleSeconds(): int
    {
        $broker = $this->passwordBroker();

        return (int) config("auth.passwords.{$broker}.throttle", 60);
    }
}
