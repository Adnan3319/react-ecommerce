<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Range checks against stock and the per-item limit live in CartService, which reads the database.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
