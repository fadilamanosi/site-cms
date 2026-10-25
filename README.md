# Site CMS

Draft/publish overlay for Laravel + Inertia marketing pages. Not tied to any one product.

Repo: [github.com/fadilamanosi/site-cms](https://github.com/fadilamanosi/site-cms.git)

## Install

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/fadilamanosi/site-cms.git"
        }
    ]
}
```

```sh
composer require fadilamanosi/site-cms:^0.1
php artisan migrate
php artisan site-cms:admin
```

## Host wiring

1. Vite alias `@site-cms` → `vendor/fadilamanosi/site-cms/resources/js`
2. Resolve Inertia pages with `resolveSiteCmsPage` (wraps the live page in the editor when `siteCms.enabled`)
3. `@include('site-cms::head')` in `app.blade.php` (needs the Inertia `$page` variable)
4. `TextUi` / `ImageUi` / `ButtonUi` honor optional `field-key`
5. `SiteCms::mergePage('home', $payload)` in the page service so SEO is in the first HTML
6. Publish `config/site-cms.php` for logo defaults and page list

Public pages render **published** values on the server. Drafts never leak to guests.
