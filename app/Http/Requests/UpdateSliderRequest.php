<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|required|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'image'       => 'sometimes|required|string',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer',
            'status'      => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}