<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use SiteCms\Http\Requests\UpsertPostRequest;
use SiteCms\Services\BlogService;
use SiteCms\Support\DeskResolver;

class BlogDeskController extends Controller
{
    public function __construct(
        private readonly BlogService $blog,
        private readonly DeskResolver $desk,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($this->blog->enabled(), 404);
        $request->session()->put('site_cms.editing', true);

        return Inertia::render('SiteCms/Blog/Index', [
            'pager' => $this->blog->deskIndex($request),
            'seo' => [
                'title' => 'Blog',
                'description' => 'Manage blog posts',
                'image' => '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->blog->enabled(), 404);
        $request->session()->put('site_cms.editing', true);

        return Inertia::render('SiteCms/Blog/Edit', [
            'post' => $this->blog->blankPost(),
            'isNew' => true,
            'seo' => [
                'title' => 'New post',
                'description' => 'Write a new blog post',
                'image' => '',
            ],
        ]);
    }

    public function edit(Request $request, int $id): Response
    {
        abort_unless($this->blog->enabled(), 404);
        $request->session()->put('site_cms.editing', true);

        return Inertia::render('SiteCms/Blog/Edit', [
            'post' => $this->blog->deskPost($id),
            'isNew' => false,
            'seo' => [
                'title' => 'Edit post',
                'description' => 'Edit blog post',
                'image' => '',
            ],
        ]);
    }

    public function store(UpsertPostRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->store($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return redirect()->to($this->blogEditPath($post->id))
            ->with('success', 'Post created.');
    }

    public function update(UpsertPostRequest $request, int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->update($id, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return redirect()->to($this->blogEditPath($post->id))
            ->with('success', 'Post saved.');
    }

    public function publish(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->publish($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back()->with('success', 'Post published.');
    }

    public function unpublish(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->unpublish($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back()->with('success', 'Post unpublished.');
    }

    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $this->blog->destroy($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->to('/'.trim($this->desk->cmsPath(), '/').'/blog')
            ->with('success', 'Post deleted.');
    }

    private function blogEditPath(int $id): string
    {
        return '/'.trim($this->desk->cmsPath(), '/').'/blog/'.$id.'/edit';
    }
}
