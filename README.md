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
4. `TextUi` / `ImageUi` / `ButtonUi` / a gallery or list wrapper honor optional `field-key`
5. `SiteCms::mergePage('home', $payload)` in the page service so SEO is in the first HTML
6. Publish `config/site-cms.php` for logo defaults, page list, and optional blog
7. When blog is enabled, install TipTap peer deps on the host (blog body editor):

```sh
npm install @tiptap/vue-3@^2.27.3 @tiptap/starter-kit@^2.27.3 @tiptap/extension-image@^2.27.3 @tiptap/extension-link@^2.27.3 @tiptap/extension-placeholder@^2.27.3 @tiptap/extension-underline@^2.27.3 @tiptap/extension-text-align@^2.27.3 @tiptap/extension-table@^2.27.3 @tiptap/extension-table-row@^2.27.3 @tiptap/extension-table-cell@^2.27.3 @tiptap/extension-table-header@^2.27.3 @tiptap/extension-highlight@^2.27.3 @tiptap/extension-character-count@^2.27.3
```


Public pages render **published** values on the server. Drafts never leak to guests.

## Desk

`SITE_CMS_UI=auto` (default):

- If the host already has a working authed dashboard, CMS mounts as `{admin}/site` under that desk. No CMS login.
- Otherwise the package ships `/cms` (editor) and `/cms/login`. Guest `/cms` redirects to login. Forgot / reset password on that login uses the host `MAIL_*` mailer (`Mail::to`, default mailer) and the host password-reset broker (`password_reset_tokens`). There is no package mailer. `/staff`, `/staff/login`, and `/staff/site` redirect to those URLs.

A desk counts as working only if the guard exists, its user model exists, the prefix has `auth:{guard}` routes, and a guest login GET exists. Config is a hint, then `staff` → `admin` → `console`. A dead hint never embeds.

```env
SITE_CMS_UI=auto
SITE_CMS_ADMIN_PATH=staff
SITE_CMS_LOGIN_PATH=cms/login
SITE_CMS_GUARD=staff
SITE_CMS_PATH=cms
SITE_CMS_BLOG=true
```

`/cms` is a Customizer: admin bar, left rail, live preview iframe, closable inspector on the right. The site header only appears inside that canvas. Preview URL is the real page with `?site_cms_preview=1`.

- **Left rail** — site identity, SEO, and which page to preview.
- **Header nav** — when blog is enabled, **Customize | Blog** opens the posts desk (`/{cms}/blog`): paginated list + TipTap editor (images use the same media library).
- **Save to draft** — writes identity / SEO from the left rail. Pencil **Update** already drafts that field; you do not need this button for those.
- **Publish** — makes unpublished drafts live. Disabled when there is nothing to publish.
- Pencils in the preview open the **right inspector** (same pencil or × closes it). The preview stays where you scrolled; the field patches in place after Update.

Field kinds:

- **text** — copy
- **link** / **button** — label, URL / email / phone, open in a new tab, or **download** (upload/replace a file)
- **image** / **gallery** — media library, upload, or URL (gallery rows collapse; add / reorder / remove)
- **list** — typed rows with a host-provided schema (add / duplicate / reorder / remove). Example socials: `label`, `href`, `handle`
- **color** / **icon** — picker or name

Kind comes from the host wrapper (`ButtonUi` → button, `ImageUi` → image, `ListUi` → list). `mailto:` and `tel:` hrefs open the email / phone fields. A `download` attribute on the host button opens the file picker.

**Media** in the top bar (and **Select media** on image fields) opens a paginated library: existing uploads, drop/upload, or paste a remote URL. Stored library paths are not shown in the paste field.

Chrome uses the host theme tokens (`--bg`, `--text`, `--gold` / `--gold-soft`, or `--background` / `--primary`). The package does not ship a second admin look.

## Lists

Host wraps a repeating block with `ListUi` (same pattern as `GalleryUi`):

```vue
<ListUi
  field-key="site.socials"
  :schema="[
    { key: 'label', label: 'Label', type: 'text' },
    { key: 'href', label: 'URL', type: 'url' },
    { key: 'handle', label: 'Handle', type: 'text' },
  ]"
  :default-items="socialLinks"
  v-slot="{ items }"
>
  <!-- host markup over items -->
</ListUi>
```

Stored value shape: `{ schema, items }`. Guests see published items only.

## Blog

Enable with `SITE_CMS_BLOG=true` (or `config/site-cms.php` `blog.enabled`).

Package owns:

- `site_cms_posts` table
- Desk at `/{cms}/blog` (paginated list) and `/{cms}/blog/new` / `/{cms}/blog/{id}/edit` (TipTap editor + media library)
- Public routes `/{blog.path}` and `/{blog.path}/{slug}`

Host owns look:

- Inertia pages `Blog/Index` and `Blog/Show` (override via `blog.index_component` / `blog.show_component`)
- Section markup that consumes the documented card shape

### Post card (documented)

```
id, slug, title, excerpt, href, cover { src, alt }, publishedAt, tags[]
```

Detail adds `body`, `seo`, `sortOrder`, `updatedAt`.

### Bound section (not the full blog page)

```php
$archive = SiteCms::blogSection('archive'); // or ['archive'], optional $limit
// → items (page 1), total, seeMore { href, label } | null
```

Rules:

- Section shows **page 1** only (`blog.section_per_page`, default 5)
- **See more** links to the main blog index (`/blog` or `/blog?tag=…`)
- Full pagination lives on the blog index only
- Guests only see **published** posts

Example home merge:

```php
return SiteCms::mergePage('home', [
    ...$pageData,
    'archive' => SiteCms::blogSection('archive'),
]);
```

## Files

Logo, favicon, social image, overlay images, and download buttons go through the media library: **upload**, reuse a file, or a **real link** (`https://…` or `/path`).

Uploads use the host `FILESYSTEM_DISK`. Override with `SITE_CMS_DISK`. Local / private disks are served at `/site-cms/media/{id}/{filename}` — there is no silent fallback to the public disk.

Size limit follows PHP `upload_max_filesize` / `post_max_size`, or `SITE_CMS_UPLOAD_MAX_KB`.
