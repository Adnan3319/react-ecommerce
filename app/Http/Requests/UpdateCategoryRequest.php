<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name'        => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->ignore($category)->whereNull('deleted_at'),
            ],
            'slug'        => [
                'sometimes', 'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category)->whereNull('deleted_at'),
            ],
            'headline'    => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'status'      => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}