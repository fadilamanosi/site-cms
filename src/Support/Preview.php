<?php

namespace SiteCms\Support;

use Illuminate\Http\Request;

class Preview
{
    public const QUERY = 'site_cms_preview';

    public static function requested(Request $request): bool
    {
        return $request->boolean(self::QUERY);
    }
}
