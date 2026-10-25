<?php

namespace SiteCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishRequest extends FormRequest
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
        return [
            'page_key' => ['required', 'string', 'max:100'],
            'scope_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
