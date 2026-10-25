<?php

namespace SiteCms\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SiteCms\Support\DeskResolver;
use SiteCms\Support\GuardResolver;
use SiteCms\Support\PageMatcher;
use SiteCms\Support\Preview;
use SiteCms\Support\UploadResolver;

class ShareService
{
    public function __construct(
        private readonly ContentService $contentService,
        private readonly PageMatcher $pageMatcher,
        private readonly GuardResolver $guardResolver,
        private readonly DeskResolver $deskResolver,
        private readonly UploadResolver $uploadResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(Request $request): array
    {
        $editing = $this->isEditing($request);
        $preview = Preview::requested($request);
        $matched = $this->pageMatcher->match($request);
        $pageKey = $this->pageKey($request, $matched);
        $status = $editing && ($preview || $request->routeIs('site-cms.*')) ? 'draft' : 'published';
        $enabled = $editing && $preview && $matched !== null && ! $request->routeIs('site-cms.*');

        $siteValues = $this->contentService->getValues('site', $status);
        $pageValues = $pageKey !== '' && $pageKey !== 'site'
            ? $this->contentService->getValues($pageKey, $status)
            : [];
        $values = [...$siteValues, ...$pageValues];
        $cmsPath = $this->deskResolver->cmsPath();
        $loginPath = $this->deskResolver->loginPath();
        $embedded = $this->deskResolver->embedded();

        return [
            'enabled' => $enabled,
            'preview' => $preview,
            'embedded' => $embedded,
            'status' => $status,
            'pageKey' => $pageKey,
            'values' => $values,
            'site' => $this->siteFrom($values),
            'pages' => $this->pageMatcher->pages(),
            'routes' => [
                'login' => $this->href($loginPath),
                'logout' => $embedded ? null : $this->href($loginPath.'/logout'),
                'forgotPassword' => Route::has('site-cms.password.request')
                    ? $this->href($loginPath.'/forgot-password')
                    : null,
                'resetPassword' => Route::has('site-cms.password.update')
                    ? $this->href($loginPath.'/reset-password')
                    : null,
                'dashboard' => $this->href($cmsPath),
                'draft' => $this->href($cmsPath.'/draft'),
                'publish' => $this->href($cmsPath.'/publish'),
                'resources' => $this->href($cmsPath.'/resources'),
                'enter' => $this->href($cmsPath.'/enter'),
                'exit' => $this->href($cmsPath.'/exit'),
            ],
            'draft' => $this->contentService->draftState($pageKey === '' ? 'home' : $pageKey),
            'upload' => [
                'accept' => $this->uploadResolver->accept('file'),
                'imageAccept' => $this->uploadResolver->accept('image'),
                'maxKilobytes' => $this->uploadResolver->maxKilobytes(),
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
        $status = $this->isEditing($request) && Preview::requested($request) ? 'draft' : 'published';
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
     * @param  array{key: string, path: string, component: string, label: string}|null  $matched
     */
    private function pageKey(Request $request, ?array $matched): string
    {
        if ($request->routeIs('site-cms.dashboard')) {
            $query = (string) $request->query('page', '');
            $fromQuery = $this->pageMatcher->byKey($query);

            return $fromQuery['key'] ?? ($this->pageMatcher->pages()[0]['key'] ?? 'home');
        }

        return $matched['key'] ?? 'home';
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

    private function href(string $path): string
    {
        return '/'.trim($path, '/');
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
