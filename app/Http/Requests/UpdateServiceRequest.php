<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceId = $this->route('service')?->id ?? $this->route('service');

        return [
            'title'             => 'sometimes|required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:services,slug,' . $serviceId,
            'short_description' => 'nullable|string|max:500',
            'description'       => 'sometimes|required|string',
            'image'             => 'nullable|string',
            'icon'              => 'nullable|string|max:100',
            'sort_order'        => 'nullable|integer',
            'status'            => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}