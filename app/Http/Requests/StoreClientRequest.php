<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name'  => 'required|string|max:255',
            'designation'  => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'message'      => 'required|string',
            'rating'       => 'nullable|integer|min:1|max:5',
            'image'        => 'nullable|string',
            'sort_order'   => 'nullable|integer',
            'status'       => 'nullable|string|in:Active,Inactive',
        ];
    }
}