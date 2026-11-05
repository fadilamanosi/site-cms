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
        'title',
        'excerpt',
        'body',
        'cover_src',
        'cover_alt',
        'status',
        'published_at',
        'seo_title',
        'seo_description',
        'seo_image',
        'tags',
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
            'sort_order' => 'integer',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
