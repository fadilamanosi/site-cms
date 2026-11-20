<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_cms_posts')) {
            return;
        }

        Schema::table('site_cms_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_cms_posts', 'published_slug')) {
                $table->string('published_slug', 160)->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_title')) {
                $table->string('published_title')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_label')) {
                $table->string('published_label', 120)->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_excerpt')) {
                $table->string('published_excerpt', 500)->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_body')) {
                $table->longText('published_body')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_cover_src')) {
                $table->string('published_cover_src')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_cover_alt')) {
                $table->string('published_cover_alt')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_seo_title')) {
                $table->string('published_seo_title')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_seo_description')) {
                $table->string('published_seo_description', 500)->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_seo_image')) {
                $table->string('published_seo_image')->nullable();
            }
            if (! Schema::hasColumn('site_cms_posts', 'published_tags')) {
                $table->json('published_tags')->nullable();
            }
        });

        if (! Schema::hasColumn('site_cms_posts', 'published_slug')) {
            return;
        }

        // Backfill live snapshot from working columns for already-published posts.
        DB::table('site_cms_posts')
            ->where('status', 'published')
            ->whereNull('published_title')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('site_cms_posts')->where('id', $row->id)->update([
                        'published_slug' => $row->slug,
                        'published_title' => $row->title,
                        'published_label' => $row->label ?? null,
                        'published_excerpt' => $row->excerpt ?? '',
                        'published_body' => $row->body,
                        'published_cover_src' => $row->cover_src,
                        'published_cover_alt' => $row->cover_alt ?? '',
                        'published_seo_title' => $row->seo_title,
                        'published_seo_description' => $row->seo_description,
                        'published_seo_image' => $row->seo_image,
                        'published_tags' => $row->tags,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_cms_posts')) {
            return;
        }

        Schema::table('site_cms_posts', function (Blueprint $table): void {
            foreach ([
                'published_slug',
                'published_title',
                'published_label',
                'published_excerpt',
                'published_body',
                'published_cover_src',
                'published_cover_alt',
                'published_seo_title',
                'published_seo_description',
                'published_seo_image',
                'published_tags',
            ] as $column) {
                if (Schema::hasColumn('site_cms_posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
