<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    /** Taka with up to two decimals, e.g. 70 or 70.50. */
    private const AMOUNT_PATTERN = '/^\d{1,6}(\.\d{1,2})?$/';

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'working_hours' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'delivery_charge_inside' => ['required', 'regex:'.self::AMOUNT_PATTERN],
            'delivery_charge_outside' => ['required', 'regex:'.self::AMOUNT_PATTERN],
        ];
    }
}
