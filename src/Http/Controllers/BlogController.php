<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Facades\SiteCms;
use SiteCms\Services\BlogService;
use SiteCms\Services\ShareService;
use SiteCms\Support\Preview;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogService $blog,
        private readonly ShareService $share,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($this->blog->enabled(), 404);

        $payload = $this->blog->indexPayload($request);

        return Inertia::render(
            (string) config('site-cms.blog.index_component', 'Blog/Index'),
            SiteCms::mergePage('blog', $payload, $request),
        );
    }

    public function show(Request $request, string $slug): Response
    {
        abort_unless($this->blog->enabled(), 404);

        $allowDraft = $this->share->isEditing($request) && Preview::requested($request);
        $payload = $this->blog->showPayload($slug, $allowDraft);

        return Inertia::render(
            (string) config('site-cms.blog.show_component', 'Blog/Show'),
            SiteCms::mergePage('blog.'.$slug, $payload, $request),
        );
    }
}
