<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Support\PageMatcher;

class EditorController extends Controller
{
    public function __construct(
        private readonly PageMatcher $pageMatcher,
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('SiteCms/Site', [
            'seo' => [
                'title' => 'Site settings',
                'description' => 'Logo, favicon, and default SEO',
                'image' => '',
            ],
        ]);
    }

    public function enter(Request $request): RedirectResponse
    {
        $pageKey = (string) $request->input('page_key', $this->pageMatcher->pages()[0]['key'] ?? 'home');
        $page = $this->pageMatcher->byKey($pageKey) ?? $this->pageMatcher->pages()[0] ?? null;
        $request->session()->put('site_cms.editing', true);

        if ($page === null) {
            return redirect()->to(url(config('site-cms.path')));
        }

        return redirect()->to(url($page['path']));
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('site_cms.editing');

        return redirect()->to(url(config('site-cms.path')));
    }
}
