<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'image'       => 'required|string',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer',
            'status'      => 'nullable|string|in:Active,Inactive',
        ];
    }
}