<?php

namespace App\Http\Requests\Admin\ContactMessage;

use App\Services\Contact\ContactMessageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of the admin message list (search box, read filter, rows per page, page).
 */
class IndexContactMessageRequest extends FormRequest
{
    private const PER_PAGE_OPTIONS = [10, 20, 50];

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
            'search' => ['nullable', 'string', 'max:100'],
            'read' => ['nullable', Rule::in([ContactMessageService::FILTER_UNREAD, ContactMessageService::FILTER_READ])],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{search: ?string, read: ?string, per_page: int}
     */
    public function filters(): array
    {
        $perPage = $this->integer('per_page', self::PER_PAGE_OPTIONS[0]);

        return [
            'search' => $this->validated('search'),
            'read' => $this->validated('read'),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0],
        ];
    }
}
