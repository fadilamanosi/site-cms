<?php

namespace SiteCms\Console;

use Illuminate\Console\Command;
use SiteCms\Support\GuardResolver;

class CreateAdminCommand extends Command
{
    protected $signature = 'site-cms:admin {email?} {--password=} {--name=}';

    protected $description = 'Create or update the Site CMS admin user (idempotent).';

    public function handle(GuardResolver $guardResolver): int
    {
        $email = $this->argument('email') ?: config('site-cms.admin.email');
        $password = $this->option('password') ?: config('site-cms.admin.password');
        $name = $this->option('name') ?: config('site-cms.admin.name');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->error('Set SITE_CMS_ADMIN_EMAIL and SITE_CMS_ADMIN_PASSWORD, or pass email and --password.');

            return self::FAILURE;
        }

        $model = $guardResolver->userModel();
        $user = $model::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => is_string($name) && $name !== '' ? $name : 'Site Admin',
                'password' => $password,
            ],
        );

        $this->info($user->wasRecentlyCreated ? "Created {$email}." : "Already exists: {$email}.");

        return self::SUCCESS;
    }
}
