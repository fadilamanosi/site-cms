<?php

namespace SiteCms\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SiteCms\Models\SitePost;
use SiteCms\Repositories\SitePostRepository;

class BlogService
{
    public function __construct(
        private readonly SitePostRepository $posts,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('site-cms.blog.enabled', false);
    }

    public function indexPath(): string
    {
        return '/'.trim((string) config('site-cms.blog.path', 'blog'), '/');
    }

    public function perPage(): int
    {
        return max(1, (int) config('site-cms.blog.per_page', 12));
    }

    public function sectionPerPage(): int
    {
        return max(1, (int) config('site-cms.blog.section_per_page', 5));
    }

    /**
     * Page-1 preview for a bound section + see-more link to the full blog index.
     *
     * @param  list<string>|string|null  $tags
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     seeMore: array{href: string, label: string}|null
     * }
     */
    public function section(string|array|null $tags = null, ?int $limit = null): array
    {
        if (! $this->enabled()) {
            return ['items' => [], 'total' => 0, 'seeMore' => null];
        }

        $tagList = $this->normalizeTags($tags);
        $limit ??= $this->sectionPerPage();
        $total = $this->posts->publishedCount($tagList);
        $items = $this->posts->publishedPage($limit, $tagList)
            ->map(fn (SitePost $post): array => $this->publicCard($post))
            ->values()
            ->all();

        $seeMore = null;
        if ($total > 0) {
            $href = $this->indexPath();
            if ($tagList !== []) {
                $href .= '?tag='.rawurlencode($tagList[0]);
            }
            $seeMore = [
                'href' => $href,
                'label' => (string) config('site-cms.blog.see_more_label', 'See more'),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'seeMore' => $seeMore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function indexPayload(Request $request): array
    {
        $tags = $this->normalizeTags($request->query('tag'));
        $page = max(1, (int) $request->query('page', 1));
        $pager = $this->posts->paginatePublished($this->perPage(), $page, $tags);

        return [
            'seo' => [
                'title' => (string) config('site-cms.blog.index_title', 'Blog'),
                'description' => (string) config('site-cms.blog.index_description', ''),
                'image' => '',
            ],
            'tag' => $tags[0] ?? null,
            'pager' => [
                'data' => collect($pager->items())->map(fn (SitePost $post): array => $this->publicCard($post))->values()->all(),
                'current_page' => $pager->currentPage(),
                'last_page' => max(1, $pager->lastPage()),
                'per_page' => $pager->perPage(),
                'total' => $pager->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showPayload(string $slug, bool $allowDraft = false): array
    {
        $post = $this->posts->findByPublicSlug($slug) ?? $this->posts->findBySlug($slug);
        if ($post === null) {
            abort(404);
        }
        if (! $allowDraft && ! $post->isPublished()) {
            abort(404);
        }

        return [
            'seo' => [
                'title' => $this->publicValue($post, 'seo_title') ?: $this->publicValue($post, 'title'),
                'description' => $this->publicValue($post, 'seo_description') ?: $this->publicValue($post, 'excerpt'),
                'image' => $this->publicValue($post, 'seo_image') ?: ($this->publicValue($post, 'cover_src') ?? ''),
            ],
            'post' => $allowDraft && ! $post->isPublished()
                ? $this->detail($post)
                : $this->publicDetail($post),
            'more' => $this->morePosts($post),
        ];
    }

    /**
     * Other published posts to list under a single post.
     *
     * @return list<array<string, mixed>>
     */
    public function morePosts(SitePost $post, ?int $limit = null): array
    {
        $limit ??= max(1, (int) config('site-cms.blog.more_per_page', 3));
        $tags = $this->normalizeTags($this->publicValue($post, 'tags') ?? []);
        $preferred = array_values(array_filter(
            $tags,
            fn (string $tag): bool => ! in_array($tag, ['archive'], true),
        ));
        $filter = $preferred !== [] ? [$preferred[0]] : null;

        $items = $this->posts->publishedExcept($post->id, $limit, $filter);
        if ($items->count() < $limit && $filter !== null) {
            $extra = $this->posts->publishedExcept($post->id, $limit, null)
                ->reject(fn (SitePost $other): bool => $items->contains('id', $other->id))
                ->take($limit - $items->count());
            $items = $items->concat($extra)->values();
        }

        return $items
            ->take($limit)
            ->map(fn (SitePost $other): array => $this->publicCard($other))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function deskList(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->posts->allForDesk()
            ->map(fn (SitePost $post): array => $this->deskDetail($post))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     status: string|null,
     *     q: string
     * }
     */
    public function deskIndex(Request $request): array
    {
        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ['draft', 'published'], true) ? $status : null;
        $search = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) config('site-cms.blog.desk_per_page', 20));
        $pager = $this->posts->paginateForDesk($perPage, $page, $status, $search !== '' ? $search : null);

        return [
            'data' => collect($pager->items())
                ->map(fn (SitePost $post): array => $this->deskDetail($post))
                ->values()
                ->all(),
            'current_page' => $pager->currentPage(),
            'last_page' => max(1, $pager->lastPage()),
            'per_page' => $pager->perPage(),
            'total' => $pager->total(),
            'status' => $status,
            'q' => $search,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function blankPost(): array
    {
        return [
            'id' => null,
            'slug' => '',
            'title' => '',
            'label' => '',
            'excerpt' => '',
            'href' => $this->indexPath().'/',
            'cover' => ['src' => '', 'alt' => ''],
            'publishedAt' => null,
            'tags' => [],
            'body' => '',
            'status' => 'draft',
            'hasUnpublishedChanges' => true,
            'seo' => [
                'title' => null,
                'description' => null,
                'image' => null,
            ],
            'sortOrder' => 0,
            'updatedAt' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deskPost(int $id): array
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        return $this->deskDetail($post);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function store(array $input): SitePost
    {
        $payload = $this->normalizeWorkingInput($input);
        if ($this->posts->findBySlug($payload['slug']) !== null) {
            throw ValidationException::withMessages(['slug' => ['That slug is already taken.']]);
        }

        $publish = (bool) ($input['publish'] ?? false)
            || (($input['status'] ?? 'draft') === 'published');

        $payload['status'] = 'draft';
        $payload['published_at'] = null;
        $post = $this->posts->create($payload);

        if ($publish) {
            return $this->promotePublished($post, $input);
        }

        return $post;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): SitePost
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        $payload = $this->normalizeWorkingInput($input, $post);
        $other = $this->posts->findBySlug($payload['slug']);
        if ($other !== null && $other->id !== $post->id) {
            throw ValidationException::withMessages(['slug' => ['That slug is already taken.']]);
        }

        // Save always writes the working copy. Status only changes via publish / unpublish.
        unset($payload['status']);
        if (array_key_exists('published_at', $input) && $input['published_at'] !== null && $input['published_at'] !== '') {
            $payload['published_at'] = $input['published_at'];
        }

        $post = $this->posts->update($post, $payload);

        $publish = (bool) ($input['publish'] ?? false);
        if ($publish) {
            return $this->promotePublished($post, $input);
        }

        return $post;
    }

    public function publish(int $id): SitePost
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        return $this->promotePublished($post);
    }

    public function unpublish(int $id): SitePost
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        return $this->posts->update($post, [
            'status' => 'draft',
        ]);
    }

    public function discardDraft(int $id): SitePost
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }
        if (! $post->isPublished() || $post->published_title === null) {
            throw ValidationException::withMessages([
                'body' => ['There is no published version to restore.'],
            ]);
        }

        return $this->posts->update($post, $post->workingFromPublishedSnapshot());
    }

    public function destroy(int $id): void
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        $this->posts->delete($post);
    }

    /**
     * @return array<string, mixed>
     */
    public function card(SitePost $post): array
    {
        return $this->publicCard($post);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(SitePost $post): array
    {
        return $this->deskDetail($post);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function promotePublished(SitePost $post, array $input = []): SitePost
    {
        $publishedAt = $input['published_at'] ?? $post->published_at ?? now();

        return $this->posts->update($post, [
            ...$post->publishedSnapshotFromWorking(),
            'status' => 'published',
            'published_at' => $publishedAt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicCard(SitePost $post): array
    {
        $slug = (string) ($this->publicValue($post, 'slug') ?: $post->slug);
        $title = (string) $this->publicValue($post, 'title');
        $coverSrc = (string) ($this->publicValue($post, 'cover_src') ?? '');
        $coverAlt = (string) ($this->publicValue($post, 'cover_alt') ?? '');

        return [
            'id' => $post->id,
            'slug' => $slug,
            'title' => $title,
            'label' => (string) ($this->publicValue($post, 'label') ?? ''),
            'excerpt' => (string) ($this->publicValue($post, 'excerpt') ?? ''),
            'href' => $this->indexPath().'/'.$slug,
            'cover' => [
                'src' => $coverSrc,
                'alt' => $coverAlt !== '' ? $coverAlt : $title,
            ],
            'publishedAt' => $post->published_at?->toIso8601String(),
            'tags' => array_values($this->normalizeTags($this->publicValue($post, 'tags') ?? [])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicDetail(SitePost $post): array
    {
        return [
            ...$this->publicCard($post),
            'body' => (string) ($this->publicValue($post, 'body') ?? ''),
            'seo' => [
                'title' => $this->publicValue($post, 'seo_title'),
                'description' => $this->publicValue($post, 'seo_description'),
                'image' => $this->publicValue($post, 'seo_image'),
            ],
            'sortOrder' => $post->sort_order,
            'updatedAt' => $post->updated_at?->toIso8601String(),
            'status' => $post->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deskDetail(SitePost $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'label' => (string) ($post->label ?? ''),
            'excerpt' => $post->excerpt,
            'href' => $this->indexPath().'/'.($post->published_slug ?: $post->slug),
            'cover' => [
                'src' => $post->cover_src ?? '',
                'alt' => $post->cover_alt !== '' ? $post->cover_alt : $post->title,
            ],
            'publishedAt' => $post->published_at?->toIso8601String(),
            'tags' => array_values($post->tags ?? []),
            'body' => $post->body ?? '',
            'seo' => [
                'title' => $post->seo_title,
                'description' => $post->seo_description,
                'image' => $post->seo_image,
            ],
            'sortOrder' => $post->sort_order,
            'updatedAt' => $post->updated_at?->toIso8601String(),
            'status' => $post->status,
            'hasUnpublishedChanges' => $post->hasUnpublishedChanges(),
        ];
    }

    private function publicValue(SitePost $post, string $field): mixed
    {
        if ($post->isPublished()) {
            $published = 'published_'.$field;
            if ($post->getAttribute($published) !== null) {
                return $post->getAttribute($published);
            }
            // Pre-migration rows / empty strings: fall back to working.
            if ($field === 'excerpt' || $field === 'cover_alt' || $field === 'body' || $field === 'label') {
                $value = $post->getAttribute($published);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return $post->getAttribute($field);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeWorkingInput(array $input, ?SitePost $existing = null): array
    {
        $title = trim((string) ($input['title'] ?? $existing?->title ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => ['Title is required.']]);
        }

        $slug = trim((string) ($input['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($title);
        }
        $slug = Str::slug($slug);
        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => ['Slug is required.']]);
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'label' => trim((string) ($input['label'] ?? $existing?->label ?? '')),
            'excerpt' => trim((string) ($input['excerpt'] ?? $existing?->excerpt ?? '')),
            'body' => (string) ($input['body'] ?? $existing?->body ?? ''),
            'cover_src' => $this->nullableString($input['cover_src'] ?? $existing?->cover_src),
            'cover_alt' => trim((string) ($input['cover_alt'] ?? $existing?->cover_alt ?? '')),
            'seo_title' => $this->nullableString($input['seo_title'] ?? $existing?->seo_title),
            'seo_description' => $this->nullableString($input['seo_description'] ?? $existing?->seo_description),
            'seo_image' => $this->nullableString($input['seo_image'] ?? $existing?->seo_image),
            'tags' => $this->normalizeTags($input['tags'] ?? $existing?->tags),
            'sort_order' => (int) ($input['sort_order'] ?? $existing?->sort_order ?? 0),
        ];
    }

    /**
     * @param  list<string>|string|null  $tags
     * @return list<string>
     */
    private function normalizeTags(mixed $tags): array
    {
        if (is_string($tags) && $tags !== '') {
            $tags = preg_split('/\s*,\s*/', $tags) ?: [];
        }
        if (! is_array($tags)) {
            return [];
        }

        $out = [];
        foreach ($tags as $tag) {
            if (! is_string($tag)) {
                continue;
            }
            $value = Str::slug(trim($tag));
            if ($value !== '' && ! in_array($value, $out, true)) {
                $out[] = $value;
            }
        }

        return $out;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
