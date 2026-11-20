<?php

namespace SiteCms\Models;

use Illuminate\Database\Eloquent\Model;

class SitePost extends Model
{
    protected $table = 'site_cms_posts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'published_slug',
        'title',
        'published_title',
        'label',
        'published_label',
        'excerpt',
        'published_excerpt',
        'body',
        'published_body',
        'cover_src',
        'published_cover_src',
        'cover_alt',
        'published_cover_alt',
        'status',
        'published_at',
        'seo_title',
        'published_seo_title',
        'seo_description',
        'published_seo_description',
        'seo_image',
        'published_seo_image',
        'tags',
        'published_tags',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'tags' => 'array',
            'published_tags' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Attributes for the public snapshot copied from the working (desk) fields.
     *
     * @return array<string, mixed>
     */
    public function publishedSnapshotFromWorking(): array
    {
        return [
            'published_slug' => $this->slug,
            'published_title' => $this->title,
            'published_label' => $this->label,
            'published_excerpt' => $this->excerpt,
            'published_body' => $this->body,
            'published_cover_src' => $this->cover_src,
            'published_cover_alt' => $this->cover_alt,
            'published_seo_title' => $this->seo_title,
            'published_seo_description' => $this->seo_description,
            'published_seo_image' => $this->seo_image,
            'published_tags' => $this->tags,
        ];
    }

    /**
     * Restore working fields from the last published snapshot.
     *
     * @return array<string, mixed>
     */
    public function workingFromPublishedSnapshot(): array
    {
        return [
            'slug' => $this->published_slug ?: $this->slug,
            'title' => $this->published_title ?? $this->title,
            'label' => $this->published_label ?? $this->label,
            'excerpt' => $this->published_excerpt ?? $this->excerpt,
            'body' => $this->published_body ?? $this->body,
            'cover_src' => $this->published_cover_src ?? $this->cover_src,
            'cover_alt' => $this->published_cover_alt ?? $this->cover_alt,
            'seo_title' => $this->published_seo_title ?? $this->seo_title,
            'seo_description' => $this->published_seo_description ?? $this->seo_description,
            'seo_image' => $this->published_seo_image ?? $this->seo_image,
            'tags' => $this->published_tags ?? $this->tags,
        ];
    }

    public function hasUnpublishedChanges(): bool
    {
        if (! $this->isPublished()) {
            return true;
        }

        if ($this->published_title === null && $this->published_body === null) {
            return true;
        }

        return $this->slug !== (string) ($this->published_slug ?? '')
            || $this->title !== (string) ($this->published_title ?? '')
            || (string) ($this->label ?? '') !== (string) ($this->published_label ?? '')
            || (string) ($this->excerpt ?? '') !== (string) ($this->published_excerpt ?? '')
            || (string) ($this->body ?? '') !== (string) ($this->published_body ?? '')
            || (string) ($this->cover_src ?? '') !== (string) ($this->published_cover_src ?? '')
            || (string) ($this->cover_alt ?? '') !== (string) ($this->published_cover_alt ?? '')
            || (string) ($this->seo_title ?? '') !== (string) ($this->published_seo_title ?? '')
            || (string) ($this->seo_description ?? '') !== (string) ($this->published_seo_description ?? '')
            || (string) ($this->seo_image ?? '') !== (string) ($this->published_seo_image ?? '')
            || json_encode(array_values($this->tags ?? [])) !== json_encode(array_values($this->published_tags ?? []));
    }
}
