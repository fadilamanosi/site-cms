<?php

namespace SiteCms\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string|null $type
 * @property string|null $size
 * @property string|null $mime_type
 * @property int|null $user_id
 */
class SiteResource extends Model
{
    protected $table = 'site_cms_resources';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'url',
        'type',
        'size',
        'mime_type',
        'user_id',
    ];
}
