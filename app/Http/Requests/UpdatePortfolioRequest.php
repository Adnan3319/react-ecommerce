<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $portfolioId = $this->route('portfolio')?->id ?? $this->route('portfolio');

        return [
            'title'             => 'sometimes|required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:portfolios,slug,' . $portfolioId,
            'category'          => 'sometimes|required|string|max:100',
            'client_name'       => 'nullable|string|max:255',
            'project_date'      => 'nullable|date',
            'location'          => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'sometimes|required|string',
            'image'             => 'sometimes|required|string',
            'gallery'           => 'nullable|array',
            'gallery.*'         => 'string',
            'sort_order'        => 'nullable|integer',
            'status'            => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}