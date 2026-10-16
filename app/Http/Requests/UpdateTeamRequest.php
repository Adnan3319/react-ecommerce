<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'designation' => 'sometimes|required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'image'       => 'nullable|string',
            'facebook'    => 'nullable|url|max:255',
            'twitter'     => 'nullable|url|max:255',
            'linkedin'    => 'nullable|url|max:255',
            'sort_order'  => 'nullable|integer',
            'status'      => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}