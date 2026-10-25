<?php

return [
    'path' => env('SITE_CMS_PATH', 'staff/site'),
    'login_path' => env('SITE_CMS_LOGIN_PATH', 'staff/login'),

    /*
    | Auto: "staff" when that guard exists, otherwise the default auth guard.
    | Set explicitly to skip detection.
    */
    'guard' => env('SITE_CMS_GUARD'),

    'user_model' => env('SITE_CMS_USER_MODEL'),

    'disk' => env('SITE_CMS_DISK', 'public'),

    'admin' => [
        'email' => env('SITE_CMS_ADMIN_EMAIL'),
        'name' => env('SITE_CMS_ADMIN_NAME', 'Site Admin'),
        'password' => env('SITE_CMS_ADMIN_PASSWORD'),
    ],

    /*
    | Public Inertia pages the editor can open.
    | path is matched against the request path; component is the Inertia name.
    */
    'pages' => [
        ['key' => 'home', 'path' => '/', 'component' => 'Home', 'label' => 'Home'],
    ],

    'defaults' => [
        'logo' => '/favicon.svg',
        'favicon' => '/favicon.svg',
        'title' => null,
        'description' => null,
        'image' => null,
    ],
];
