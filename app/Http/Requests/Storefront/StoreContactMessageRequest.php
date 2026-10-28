<?php

namespace App\Http\Requests\Storefront;

use App\Models\ContactMessage;
use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /** Hidden field real visitors never fill in; bots do. */
    public const HONEYPOT_FIELD = 'website';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:'.ContactMessage::MAX_NAME_LENGTH],
            'phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Enter a valid mobile number, e.g. 01XXXXXXXXX.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:'.ContactMessage::MAX_MESSAGE_LENGTH],
            self::HONEYPOT_FIELD => ['nullable', 'string'],
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input(self::HONEYPOT_FIELD));
    }

    /**
     * @return array{name: string, mobile: string, email: ?string, message: string}
     */
    public function messageData(): array
    {
        $input = $this->validated();

        return [
            'name' => $input['name'],
            'mobile' => Phone::normalize($input['phone']),
            'email' => $input['email'] ?? null,
            'message' => $input['message'],
        ];
    }
}
