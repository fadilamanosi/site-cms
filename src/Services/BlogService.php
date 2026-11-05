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
            ->map(fn (SitePost $post): array => $this->card($post))
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
                'data' => collect($pager->items())->map(fn (SitePost $post): array => $this->card($post))->values()->all(),
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
        $post = $this->posts->findBySlug($slug);
        if ($post === null) {
            abort(404);
        }
        if (! $allowDraft && ! $post->isPublished()) {
            abort(404);
        }

        return [
            'seo' => [
                'title' => $post->seo_title ?: $post->title,
                'description' => $post->seo_description ?: $post->excerpt,
                'image' => $post->seo_image ?: ($post->cover_src ?? ''),
            ],
            'post' => $this->detail($post),
        ];
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
            ->map(fn (SitePost $post): array => [
                ...$this->detail($post),
                'status' => $post->status,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function store(array $input): SitePost
    {
        $payload = $this->normalizeInput($input);
        if ($this->posts->findBySlug($payload['slug']) !== null) {
            throw ValidationException::withMessages(['slug' => ['That slug is already taken.']]);
        }

        return $this->posts->create($payload);
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

        $payload = $this->normalizeInput($input, $post);
        $other = $this->posts->findBySlug($payload['slug']);
        if ($other !== null && $other->id !== $post->id) {
            throw ValidationException::withMessages(['slug' => ['That slug is already taken.']]);
        }

        return $this->posts->update($post, $payload);
    }

    public function publish(int $id): SitePost
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            abort(404);
        }

        return $this->posts->update($post, [
            'status' => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);
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
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'href' => $this->indexPath().'/'.$post->slug,
            'cover' => [
                'src' => $post->cover_src ?? '',
                'alt' => $post->cover_alt !== '' ? $post->cover_alt : $post->title,
            ],
            'publishedAt' => $post->published_at?->toIso8601String(),
            'tags' => array_values($post->tags ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(SitePost $post): array
    {
        return [
            ...$this->card($post),
            'body' => $post->body ?? '',
            'seo' => [
                'title' => $post->seo_title,
                'description' => $post->seo_description,
                'image' => $post->seo_image,
            ],
            'sortOrder' => $post->sort_order,
            'updatedAt' => $post->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input, ?SitePost $existing = null): array
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

        $status = ($input['status'] ?? $existing?->status ?? 'draft') === 'published' ? 'published' : 'draft';
        $publishedAt = $input['published_at'] ?? $existing?->published_at;
        if ($status === 'published' && empty($publishedAt)) {
            $publishedAt = now();
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => trim((string) ($input['excerpt'] ?? $existing?->excerpt ?? '')),
            'body' => (string) ($input['body'] ?? $existing?->body ?? ''),
            'cover_src' => $this->nullableString($input['cover_src'] ?? $existing?->cover_src),
            'cover_alt' => trim((string) ($input['cover_alt'] ?? $existing?->cover_alt ?? '')),
            'status' => $status,
            'published_at' => $publishedAt,
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
