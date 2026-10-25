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
2. Resolve Inertia pages with `resolveSiteCmsPage`
3. `@include('site-cms::head')` in `app.blade.php` (needs the Inertia `$page` variable)
4. `TextUi` / `ImageUi` / `ButtonUi` / a gallery wrapper honor optional `field-key`
5. `SiteCms::mergePage('home', $payload)` in the page service so SEO is in the first HTML
6. Publish `config/site-cms.php` for logo defaults and page list

Public pages render **published** values on the server. Drafts never leak to guests.

## Desk

`SITE_CMS_UI=auto` (default):

- If the host already has a working authed dashboard, CMS mounts as `{admin}/site` under that desk. No CMS login.
- Otherwise the package ships `/staff/login` and `/staff/site`.

A desk counts as working only if the guard exists, its user model exists, the prefix has `auth:{guard}` routes, and a guest login GET exists. Config is a hint, then `staff` → `admin` → `console`. A dead hint never embeds.

```env
SITE_CMS_UI=auto
SITE_CMS_ADMIN_PATH=staff
SITE_CMS_LOGIN_PATH=staff/login
SITE_CMS_GUARD=staff
SITE_CMS_PATH=staff/site
```

`/staff/site` is a Customizer: admin bar, left rail, live preview iframe, closable inspector on the right. The site header only appears inside that canvas. Preview URL is the real page with `?site_cms_preview=1`.

- **Left rail** — site identity, SEO, and which page to preview.
- **Save to draft** — writes identity / SEO from the left rail. Pencil **Update** already drafts that field; you do not need this button for those.
- **Publish** — makes unpublished drafts live. Disabled when there is nothing to publish.
- Pencils in the preview open the **right inspector** (same pencil or × closes it). The preview stays where you scrolled; the field patches in place after Update.

Field kinds:

- **text** — copy
- **link** / **button** — label, URL / email / phone, open in a new tab, or **download** (upload/replace a file)
- **image** / **gallery** — media library, upload, or URL (gallery rows collapse)
- **color** / **icon** — picker or name

Kind comes from the host wrapper (`ButtonUi` → button, `ImageUi` → image). `mailto:` and `tel:` hrefs open the email / phone fields. A `download` attribute on the host button opens the file picker.

**Media** in the top bar (and **Select media** on image fields) opens a paginated library: existing uploads, drop/upload, or paste a remote URL. Stored library paths are not shown in the paste field.

Chrome uses the host theme tokens (`--bg`, `--text`, `--gold` / `--gold-soft`, or `--background` / `--primary`). The package does not ship a second admin look.

## Files

Logo, favicon, social image, overlay images, and download buttons go through the media library: **upload**, reuse a file, or a **real link** (`https://…` or `/path`).

Uploads use the host `FILESYSTEM_DISK`. Override with `SITE_CMS_DISK`. Local / private disks are served at `/site-cms/media/{id}/{filename}` — there is no silent fallback to the public disk.

Size limit follows PHP `upload_max_filesize` / `post_max_size`, or `SITE_CMS_UPLOAD_MAX_KB`.
