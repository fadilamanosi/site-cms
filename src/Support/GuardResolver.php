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
}
