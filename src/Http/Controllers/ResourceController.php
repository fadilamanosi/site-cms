<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SiteCms\Http\Requests\StoreResourceRequest;
use SiteCms\Services\ResourceService;
use SiteCms\Support\GuardResolver;

class ResourceController extends Controller
{
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly GuardResolver $guardResolver,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->resourceService->list()->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'mime_type' => $row->mime_type,
            ])->values(),
        ]);
    }

    public function store(StoreResourceRequest $request): JsonResponse
    {
        $user = auth($this->guardResolver->name())->user();
        $resource = $this->resourceService->store(
            $request->file('file'),
            $user?->getAuthIdentifier() ? (int) $user->getAuthIdentifier() : null,
        );

        return response()->json([
            'data' => [
                'id' => $resource->id,
                'name' => $resource->name,
                'url' => $resource->url,
                'mime_type' => $resource->mime_type,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->resourceService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['message' => 'Deleted.']);
    }
}
