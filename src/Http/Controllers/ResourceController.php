<?php

namespace SiteCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use SiteCms\Http\Requests\StoreResourceRequest;
use SiteCms\Services\ResourceService;
use SiteCms\Support\GuardResolver;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly GuardResolver $guardResolver,
    ) {}

    public function show(int $id): StreamedResponse
    {
        return $this->resourceService->stream($id);
    }

    public function index(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString() === 'image' ? 'image' : null;
        $paginator = $this->resourceService->list(
            max(1, $request->integer('page', 1)),
            $request->integer('per_page', 24),
            $type,
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'mime_type' => $row->mime_type,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => max(1, $paginator->lastPage()),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreResourceRequest $request): JsonResponse
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $user = auth($this->guardResolver->name())->user();
        $resource = $this->resourceService->store(
            $file,
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
