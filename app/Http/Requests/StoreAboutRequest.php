<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => 'required|string|max:255',
            'subtitle'           => 'nullable|string|max:255',
            'description'        => 'required|string',
            'mission'            => 'nullable|string',
            'vision'             => 'nullable|string',
            'image'              => 'nullable|string',
            'experience_years'   => 'nullable|integer',
            'completed_projects' => 'nullable|integer',
            'satisfied_clients'  => 'nullable|integer',
            'status'             => 'nullable|string|in:Active,Inactive',
        ];
    }
}