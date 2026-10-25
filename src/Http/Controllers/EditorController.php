<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Support\DeskResolver;
use SiteCms\Support\PageMatcher;
use SiteCms\Support\Preview;

class EditorController extends Controller
{
    public function __construct(
        private readonly PageMatcher $pageMatcher,
        private readonly DeskResolver $deskResolver,
    ) {}

    public function dashboard(Request $request): Response
    {
        $request->session()->put('site_cms.editing', true);
        $page = $this->resolvePage((string) $request->query('page', ''));

        $base = url($page['path'] === '/' ? '/' : $page['path']);
        $page['preview_url'] = $base.(str_contains($base, '?') ? '&' : '?').Preview::QUERY.'=1';

        return Inertia::render('SiteCms/Editor', [
            'editorPage' => $page,
            'seo' => [
                'title' => 'Customize',
                'description' => 'Edit the live site',
                'image' => '',
            ],
        ]);
    }

    public function enter(Request $request): RedirectResponse
    {
        $request->session()->put('site_cms.editing', true);
        $page = $this->resolvePage((string) $request->input('page_key', ''));

        return redirect()->to(url($this->deskResolver->cmsPath()).'?page='.rawurlencode($page['key']));
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('site_cms.editing');
        $home = $this->pageMatcher->pages()[0]['path'] ?? '/';

        return redirect()->to(url($home));
    }

    /**
     * @return array{key: string, path: string, component: string, label: string, preview_url?: string}
     */
    private function resolvePage(string $pageKey): array
    {
        $page = $this->pageMatcher->byKey($pageKey) ?? $this->pageMatcher->pages()[0] ?? null;

        return $page ?? [
            'key' => 'home',
            'path' => '/',
            'component' => 'Home',
            'label' => 'Home',
        ];
    }
}
