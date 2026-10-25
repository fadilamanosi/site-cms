<?php

namespace SiteCms\Services;

use Illuminate\Http\Request;
use SiteCms\Support\GuardResolver;
use SiteCms\Support\PageMatcher;

class ShareService
{
    public function __construct(
        private readonly ContentService $contentService,
        private readonly PageMatcher $pageMatcher,
        private readonly GuardResolver $guardResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(Request $request): array
    {
        $editing = $this->isEditing($request);
        $matched = $this->pageMatcher->match($request);
        $pageKey = $matched['key'] ?? 'home';
        $status = $editing ? 'draft' : 'published';
        $enabled = $editing && $matched !== null && ! $request->routeIs('site-cms.*');

        $siteValues = $this->contentService->getValues('site', $status);
        $pageValues = $matched ? $this->contentService->getValues($pageKey, $status) : [];
        $values = [...$siteValues, ...$pageValues];

        return [
            'enabled' => $enabled,
            'status' => $status,
            'pageKey' => $pageKey,
            'values' => $values,
            'site' => $this->siteFrom($values),
            'pages' => $this->pageMatcher->pages(),
            'routes' => [
                'login' => url(config('site-cms.login_path')),
                'dashboard' => url(config('site-cms.path')),
                'draft' => url(config('site-cms.path').'/draft'),
                'publish' => url(config('site-cms.path').'/publish'),
                'resources' => url(config('site-cms.path').'/resources'),
                'enter' => url(config('site-cms.path').'/enter'),
                'exit' => url(config('site-cms.path').'/exit'),
            ],
        ];
    }

    /**
     * Overlay published (or draft, while editing) SEO onto a page payload.
     *
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public function mergePage(string $pageKey, array $page, ?Request $request = null): array
    {
        $request ??= request();
        $status = $this->isEditing($request) ? 'draft' : 'published';
        $siteValues = $this->contentService->getValues('site', $status);
        $pageValues = $this->contentService->getValues($pageKey, $status);
        $values = [...$siteValues, ...$pageValues];
        $site = $this->siteFrom($values);

        $seo = is_array($page['seo'] ?? null) ? $page['seo'] : [];
        $seo['title'] = $this->scalar($pageValues['seo.title'] ?? null)
            ?? $site['title']
            ?? ($seo['title'] ?? null);
        $seo['description'] = $this->scalar($pageValues['seo.description'] ?? null)
            ?? $site['description']
            ?? ($seo['description'] ?? null);
        $seo['image'] = $this->scalar($pageValues['seo.image'] ?? null)
            ?? $site['image']
            ?? ($seo['image'] ?? null);

        $page['seo'] = $seo;

        return $page;
    }

    public function isEditing(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $guard = $this->guardResolver->name();

        return $request->session()->get('site_cms.editing') === true
            && auth($guard)->check();
    }

    /**
     * @param  array<string, array{kind: string, value: mixed}>  $values
     * @return array{logo: string|null, favicon: string|null, title: string|null, description: string|null, image: string|null}
     */
    private function siteFrom(array $values): array
    {
        $defaults = config('site-cms.defaults', []);

        return [
            'logo' => $this->scalar($values['site.logo'] ?? null) ?? ($defaults['logo'] ?? null),
            'favicon' => $this->scalar($values['site.favicon'] ?? null) ?? ($defaults['favicon'] ?? null),
            'title' => $this->scalar($values['site.title'] ?? null) ?? ($defaults['title'] ?? config('app.name')),
            'description' => $this->scalar($values['site.description'] ?? null) ?? ($defaults['description'] ?? null),
            'image' => $this->scalar($values['site.image'] ?? null) ?? ($defaults['image'] ?? null),
        ];
    }

    private function scalar(mixed $entry): ?string
    {
        if (! is_array($entry)) {
            return null;
        }
        $value = $entry['value'] ?? null;
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
