<?php

namespace SiteCms\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use SiteCms\Models\SitePost;

class SitePostRepository
{
    public function find(int $id): ?SitePost
    {
        return SitePost::query()->find($id);
    }

    public function findBySlug(string $slug): ?SitePost
    {
        return SitePost::query()->where('slug', $slug)->first();
    }

    /**
     * @return Collection<int, SitePost>
     */
    public function allForDesk(): Collection
    {
        return SitePost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, SitePost>
     */
    public function paginateForDesk(int $perPage = 20, int $page = 1, ?string $status = null, ?string $search = null): LengthAwarePaginator
    {
        $query = SitePost::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($status === 'published' || $status === 'draft') {
            $query->where('status', $status);
        }

        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('excerpt', 'like', $term);
            });
        }

        return $query->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param  list<string>|null  $tags
     * @return LengthAwarePaginator<int, SitePost>
     */
    public function paginatePublished(int $perPage, int $page = 1, ?array $tags = null): LengthAwarePaginator
    {
        return $this->publishedQuery($tags)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param  list<string>|null  $tags
     * @return Collection<int, SitePost>
     */
    public function publishedPage(int $limit, ?array $tags = null): Collection
    {
        return $this->publishedQuery($tags)
            ->limit($limit)
            ->get();
    }

    public function publishedCount(?array $tags = null): int
    {
        return $this->publishedQuery($tags)->count();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SitePost
    {
        return SitePost::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(SitePost $post, array $attributes): SitePost
    {
        $post->fill($attributes);
        $post->save();

        return $post->refresh();
    }

    public function delete(SitePost $post): void
    {
        $post->delete();
    }

    /**
     * @param  list<string>|null  $tags
     * @return Builder<SitePost>
     */
    private function publishedQuery(?array $tags = null): Builder
    {
        $query = SitePost::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($tags !== null && $tags !== []) {
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        return $query;
    }
}
