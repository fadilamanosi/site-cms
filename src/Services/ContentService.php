<?php

namespace SiteCms\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use SiteCms\Repositories\ContentValueRepository;

class ContentService
{
    public function __construct(
        private readonly ContentValueRepository $contentValueRepository,
    ) {}

    /**
     * @return array<string, array{kind: string, value: mixed}>
     */
    public function getValues(string $pageKey, string $status, ?string $scopeKey = null): array
    {
        $out = [];
        foreach ($this->contentValueRepository->listFor($pageKey, $status, $scopeKey) as $row) {
            $out[$row->field_key] = [
                'kind' => $row->kind,
                'value' => $row->value,
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array{field_key: string, kind: string, value: mixed}> $fields
     */
    public function upsertDraft(string $pageKey, array $fields, ?string $scopeKey = null): void
    {
        foreach ($fields as $field) {
            $kind = $field['kind'];
            $this->assertKind($kind, $field['value']);
            $storagePage = str_starts_with($field['field_key'], 'site.') ? 'site' : $pageKey;

            $this->contentValueRepository->upsertDraft(
                $storagePage,
                $field['field_key'],
                [
                    'kind' => $kind,
                    'value' => $this->normalizeValue($kind, $field['value']),
                ],
                $storagePage === 'site' ? null : $scopeKey,
            );
        }
    }

    public function publish(string $pageKey, ?string $scopeKey = null): void
    {
        DB::transaction(function () use ($pageKey, $scopeKey): void {
            $this->publishPage('site', null);
            if ($pageKey !== 'site') {
                $this->publishPage($pageKey, $scopeKey);
            }
        });
    }

    private function publishPage(string $pageKey, ?string $scopeKey): void
    {
        $drafts = $this->contentValueRepository->listFor($pageKey, 'draft', $scopeKey);
        $this->contentValueRepository->deletePublishedFor($pageKey, $scopeKey);
        foreach ($drafts as $draft) {
            $this->contentValueRepository->createPublishedFromDraft($draft);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeValue(string $kind, mixed $value): array
    {
        if (is_array($value)) {
            if ($kind === 'text' && ! array_key_exists('value', $value) && isset($value[0])) {
                return ['value' => (string) $value[0]];
            }

            if ($kind === 'text' && ! array_key_exists('value', $value) && array_is_list($value) === false) {
                return $value;
            }

            if (in_array($kind, ['image', 'color', 'icon'], true) && array_key_exists('value', $value)) {
                return $value;
            }

            if (in_array($kind, ['button', 'gallery'], true)) {
                return $value;
            }
        }

        return ['value' => $value];
    }

    private function assertKind(string $kind, mixed $value): void
    {
        if (! in_array($kind, ['text', 'image', 'color', 'icon', 'button', 'gallery'], true)) {
            throw new InvalidArgumentException("Unknown kind: {$kind}");
        }

        if ($kind === 'button' && is_array($value) && array_key_exists('label', $value) && ! is_string($value['label'])) {
            throw new InvalidArgumentException('Button label must be a string.');
        }
    }
}
