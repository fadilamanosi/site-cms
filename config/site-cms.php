<?php

return [
    /*
    | auto: embed when a working authed desk is found, otherwise standalone.
    | embedded: still probes; falls back to standalone if the desk is dead.
    | standalone: always use package login + settings pages.
    */
    'ui' => env('SITE_CMS_UI', 'auto'),

    /*
    | Optional desk hints. Tried first, then staff → admin → console.
    | Embed only if the probe finds a real authed dashboard.
    */
    'admin_path' => env('SITE_CMS_ADMIN_PATH'),
    'login_path' => env('SITE_CMS_LOGIN_PATH'),
    'guard' => env('SITE_CMS_GUARD'),

    /*
    | CMS settings URL. Default: {admin_path}/site when embedded, or /cms standalone.
    */
    'path' => env('SITE_CMS_PATH'),

    'user_model' => env('SITE_CMS_USER_MODEL'),

    /*
    | Empty: use the host FILESYSTEM_DISK. Set SITE_CMS_DISK to force a disk.
    | Private local files are served at /site-cms/media/{id}.
    */
    'disk' => env('SITE_CMS_DISK'),

    'upload' => [
        'directory' => env('SITE_CMS_UPLOAD_DIRECTORY', 'site-cms'),
        'max_kilobytes' => env('SITE_CMS_UPLOAD_MAX_KB'),
        'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rtf'],
    ],

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
