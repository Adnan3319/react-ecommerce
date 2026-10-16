<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:portfolios,slug',
            'category'          => 'required|string|max:100',
            'client_name'       => 'nullable|string|max:255',
            'project_date'      => 'nullable|date',
            'location'          => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'required|string',
            'image'             => 'required|string',
            'gallery'           => 'nullable|array',
            'gallery.*'         => 'string',
            'sort_order'        => 'nullable|integer',
            'status'            => 'nullable|string|in:Active,Inactive',
        ];
    }
}