<?php

namespace SiteCms\Support;

use Illuminate\Http\Request;

class PageMatcher
{
    /**
     * @return array{key: string, path: string, component: string, label: string}|null
     */
    public function match(Request $request): ?array
    {
        $path = '/'.ltrim($request->path(), '/');

        foreach ($this->pages() as $page) {
            if ($page['path'] === $path) {
                return $page;
            }
        }

        return null;
    }

    /**
     * @return array{key: string, path: string, component: string, label: string}|null
     */
    public function byKey(string $key): ?array
    {
        foreach ($this->pages() as $page) {
            if ($page['key'] === $key) {
                return $page;
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, path: string, component: string, label: string}>
     */
    public function pages(): array
    {
        $out = [];
        foreach (config('site-cms.pages', []) as $page) {
            if (! is_array($page) || ! isset($page['key'], $page['path'])) {
                continue;
            }
            $out[] = [
                'key' => (string) $page['key'],
                'path' => '/'.ltrim((string) $page['path'], '/') ?: '/',
                'component' => (string) ($page['component'] ?? 'Home'),
                'label' => (string) ($page['label'] ?? $page['key']),
            ];
        }

        return $out;
    }
}
