<?php

namespace SiteCms\Support;

class GuardResolver
{
    public function name(): string
    {
        $configured = config('site-cms.guard');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $guards = array_keys(config('auth.guards', []));
        if (in_array('staff', $guards, true)) {
            return 'staff';
        }

        return (string) config('auth.defaults.guard', 'web');
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
