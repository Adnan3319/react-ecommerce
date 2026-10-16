<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $blogId = $this->route('blog')?->id ?? $this->route('blog');

        return [
            'title'             => 'sometimes|required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:blogs,slug,' . $blogId,
            'category'          => 'sometimes|required|string|max:100',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'sometimes|required|string',
            'image'             => 'nullable|string',
            'author_name'       => 'nullable|string|max:255',
            'views_count'       => 'nullable|integer',
            'sort_order'        => 'nullable|integer',
            'status'            => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}