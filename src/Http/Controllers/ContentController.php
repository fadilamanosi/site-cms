<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use SiteCms\Http\Requests\PublishRequest;
use SiteCms\Http\Requests\UpsertDraftRequest;
use SiteCms\Services\ContentService;

class ContentController extends Controller
{
    public function __construct(
        private readonly ContentService $contentService,
    ) {}

    public function draft(UpsertDraftRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $this->contentService->upsertDraft(
            $payload['page_key'],
            $payload['fields'],
            $payload['scope_key'] ?? null,
        );

        return back();
    }

    public function publish(PublishRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $this->contentService->publish(
            $payload['page_key'],
            $payload['scope_key'] ?? null,
        );

        return back();
    }
}
