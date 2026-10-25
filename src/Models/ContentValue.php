<?php

namespace SiteCms\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $page_key
 * @property string|null $scope_key
 * @property string $field_key
 * @property string $kind
 * @property array<string, mixed> $value
 * @property string $status
 */
class ContentValue extends Model
{
    protected $table = 'site_cms_content_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'page_key',
        'scope_key',
        'field_key',
        'kind',
        'value',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
