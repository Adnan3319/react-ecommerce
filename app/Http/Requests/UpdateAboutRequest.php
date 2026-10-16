<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => 'sometimes|required|string|max:255',
            'subtitle'           => 'nullable|string|max:255',
            'description'        => 'sometimes|required|string',
            'mission'            => 'nullable|string',
            'vision'             => 'nullable|string',
            'image'              => 'nullable|string',
            'experience_years'   => 'nullable|integer',
            'completed_projects' => 'nullable|integer',
            'satisfied_clients'  => 'nullable|integer',
            'status'             => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}