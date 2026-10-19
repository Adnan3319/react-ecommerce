<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Build the slug from the name when none is given,
     * so the unique check below also covers the generated slug.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'        => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
            'slug'        => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->whereNull('deleted_at'),
            ],
            'headline'    => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'status'      => 'nullable|string|in:Active,Inactive',
        ];
    }
}