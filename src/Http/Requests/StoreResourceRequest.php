<?php

namespace SiteCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'file' => ['required', 'file', 'max:12288', 'mimes:jpg,jpeg,png,gif,webp,svg,ico'],
        ];
    }
}
