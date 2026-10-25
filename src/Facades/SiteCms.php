<?php

namespace SiteCms\Facades;

use Illuminate\Support\Facades\Facade;
use SiteCms\Services\ShareService;

/**
 * @method static array<string, mixed> mergePage(string $pageKey, array $page, ?\Illuminate\Http\Request $request = null)
 */
class SiteCms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShareService::class;
    }
}
