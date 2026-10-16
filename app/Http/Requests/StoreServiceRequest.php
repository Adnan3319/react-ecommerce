<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:services,slug',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'required|string',
            'image'             => 'nullable|string',
            'icon'              => 'nullable|string|max:100',
            'sort_order'        => 'nullable|integer',
            'status'            => 'nullable|string|in:Active,Inactive',
        ];
    }
}