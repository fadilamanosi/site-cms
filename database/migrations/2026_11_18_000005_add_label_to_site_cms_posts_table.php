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

        if (! Schema::hasColumn('site_cms_posts', 'label')) {
            Schema::table('site_cms_posts', function (Blueprint $table): void {
                $table->string('label', 120)->default('')->after('title');
            });
        }

        $this->backfillLabelsFromTags();
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_cms_posts') || ! Schema::hasColumn('site_cms_posts', 'label')) {
            return;
        }

        Schema::table('site_cms_posts', function (Blueprint $table): void {
            $table->dropColumn('label');
        });
    }

    private function backfillLabelsFromTags(): void
    {
        $rows = DB::table('site_cms_posts')->select(['id', 'label', 'tags'])->get();

        foreach ($rows as $row) {
            $current = is_string($row->label) ? trim($row->label) : '';
            if ($current !== '') {
                continue;
            }

            $tags = $this->decodeTags($row->tags);
            $label = '';
            $kept = [];

            foreach ($tags as $tag) {
                if ($label === '' && ! in_array($tag, ['perspectives', 'archive'], true)) {
                    $label = $tag;
                    continue;
                }
                $kept[] = $tag;
            }

            if ($label === '') {
                continue;
            }

            DB::table('site_cms_posts')->where('id', $row->id)->update([
                'label' => $label,
                'tags' => json_encode(array_values($kept)),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function decodeTags(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $tag) {
            if (! is_string($tag)) {
                continue;
            }
            $value = trim($tag);
            if ($value !== '') {
                $out[] = $value;
            }
        }

        return $out;
    }
};
