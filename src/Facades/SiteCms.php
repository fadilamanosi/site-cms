<?php

namespace SiteCms\Facades;

use Illuminate\Support\Facades\Facade;
use SiteCms\Services\ShareService;

/**
 * @method static array<string, mixed> mergePage(string $pageKey, array $page, ?\Illuminate\Http\Request $request = null)
 * @method static array{items: list<array<string, mixed>>, total: int, seeMore: array{href: string, label: string}|null} blogSection(string|array|null $tags = null, ?int $limit = null)
 * @method static string blogPath()
 */
class SiteCms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShareService::class;
    }
}
