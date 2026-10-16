<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'sometimes|required|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'sometimes|required|string',
            'is_read' => 'nullable|boolean',
            'status'  => 'sometimes|required|string|in:Active,Inactive',
        ];
    }
}