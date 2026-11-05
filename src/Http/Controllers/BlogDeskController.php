<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use SiteCms\Http\Requests\UpsertPostRequest;
use SiteCms\Services\BlogService;

class BlogDeskController extends Controller
{
    public function __construct(
        private readonly BlogService $blog,
    ) {}

    public function store(UpsertPostRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->store($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back();
    }

    public function update(UpsertPostRequest $request, int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->update($id, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back();
    }

    public function publish(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->publish($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back();
    }

    public function unpublish(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $post = $this->blog->unpublish($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'post' => $this->blog->detail($post)]);
        }

        return back();
    }

    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        abort_unless($this->blog->enabled(), 404);
        $this->blog->destroy($id);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
