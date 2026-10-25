<?php

namespace SiteCms\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use SiteCms\Models\SiteResource;
use SiteCms\Repositories\SiteResourceRepository;

class ResourceService
{
    public function __construct(
        private readonly SiteResourceRepository $siteResourceRepository,
    ) {}

    /**
     * @return Collection<int, SiteResource>
     */
    public function list(): Collection
    {
        return $this->siteResourceRepository->listLatest();
    }

    public function store(UploadedFile $file, ?int $userId): SiteResource
    {
        $disk = (string) config('site-cms.disk', 'public');
        $path = $file->store('site-cms', $disk);
        $url = Storage::disk($disk)->url($path);

        return $this->siteResourceRepository->create([
            'name' => $file->getClientOriginalName(),
            'url' => $url,
            'type' => $file->getClientOriginalExtension() ?: null,
            'size' => (string) $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'user_id' => $userId,
        ]);
    }

    public function delete(int $id): bool
    {
        $resource = $this->siteResourceRepository->find($id);
        if (! $resource) {
            return false;
        }

        $this->siteResourceRepository->delete($resource);

        return true;
    }
}
