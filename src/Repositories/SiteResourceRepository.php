<?php

namespace SiteCms\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use SiteCms\Models\SiteResource;

class SiteResourceRepository
{
    public function ready(): bool
    {
        return Schema::hasTable('site_cms_resources');
    }

    /**
     * @return LengthAwarePaginator<int, SiteResource>
     */
    public function paginate(int $page = 1, int $perPage = 24, ?string $type = null): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = min(48, max(1, $perPage));

        if (! $this->ready()) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $query = SiteResource::query()->latest();
        if ($type === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): SiteResource
    {
        return SiteResource::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(SiteResource $resource, array $attributes): SiteResource
    {
        $resource->fill($attributes);
        $resource->save();

        return $resource;
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
