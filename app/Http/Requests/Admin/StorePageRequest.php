<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'sub_title' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('pages', 'slug')],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::enum(ActiveStatus::class)],
        ];
    }
}
