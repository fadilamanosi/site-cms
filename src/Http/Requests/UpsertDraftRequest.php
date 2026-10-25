<?php

namespace SiteCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertDraftRequest extends FormRequest
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.field_key' => ['required', 'string', 'max:150'],
            'fields.*.kind' => ['required', 'in:text,image,color,icon,button,link,gallery'],
            'fields.*.value' => ['required'],
        ];
    }
}
