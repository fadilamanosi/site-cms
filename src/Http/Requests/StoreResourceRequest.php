<?php

namespace SiteCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SiteCms\Support\UploadResolver;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $upload = app(UploadResolver::class);

        return [
            'file' => [
                'required',
                'file',
                'max:'.$upload->maxKilobytes(),
                'mimes:'.implode(',', $upload->mimes()),
            ],
        ];
    }
}
