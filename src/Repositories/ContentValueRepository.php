<?php

namespace SiteCms\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use SiteCms\Models\ContentValue;

class ContentValueRepository
{
    public function ready(): bool
    {
        return Schema::hasTable('site_cms_content_values');
    }

    /**
     * @return Collection<int, ContentValue>
     */
    public function listFor(string $pageKey, string $status, ?string $scopeKey = null): Collection
    {
        if (! $this->ready()) {
            return collect();
        }

        return ContentValue::query()
            ->where('page_key', $pageKey)
            ->where('status', $status)
            ->where('scope_key', $scopeKey ?? '')
            ->get();
    }

    /**
     * @param array{kind: string, value: array<string, mixed>} $payload
     */
    public function upsertDraft(string $pageKey, string $fieldKey, array $payload, ?string $scopeKey = null): ContentValue
    {
        /** @var ContentValue $row */
        $row = ContentValue::query()->updateOrCreate(
            [
                'page_key' => $pageKey,
                'scope_key' => $scopeKey ?? '',
                'field_key' => $fieldKey,
                'status' => 'draft',
            ],
            [
                'kind' => $payload['kind'],
                'value' => $payload['value'],
            ],
        );

        return $row;
    }

    public function deletePublishedFor(string $pageKey, ?string $scopeKey = null): void
    {
        ContentValue::query()
            ->where('page_key', $pageKey)
            ->where('status', 'published')
            ->where('scope_key', $scopeKey ?? '')
            ->delete();
    }

    public function createPublishedFromDraft(ContentValue $draft): ContentValue
    {
        /** @var ContentValue $published */
        $published = ContentValue::query()->create([
            'page_key' => $draft->page_key,
            'scope_key' => $draft->scope_key,
            'field_key' => $draft->field_key,
            'kind' => $draft->kind,
            'value' => $draft->value,
            'status' => 'published',
        ]);

        return $published;
    }
}
