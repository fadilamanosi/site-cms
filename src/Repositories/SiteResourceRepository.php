<?php

namespace SiteCms\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use SiteCms\Models\SiteResource;

class SiteResourceRepository
{
    public function ready(): bool
    {
        return Schema::hasTable('site_cms_resources');
    }

    /**
     * @return Collection<int, SiteResource>
     */
    public function listLatest(int $limit = 48): Collection
    {
        if (! $this->ready()) {
            return collect();
        }

        return SiteResource::query()->latest()->limit($limit)->get();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): SiteResource
    {
        return SiteResource::query()->create($attributes);
    }

    public function find(int $id): ?SiteResource
    {
        if (! $this->ready()) {
            return null;
        }

        return SiteResource::query()->find($id);
    }

    public function delete(SiteResource $resource): void
    {
        $resource->delete();
    }
}
