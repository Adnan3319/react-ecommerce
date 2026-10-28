<?php

namespace App\Http\Requests\Admin\Notification;

use App\Notifications\NewContactMessageNotification;
use App\Notifications\NewOrderNotification;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of the full notification list (type filter, read filter, page).
 */
class IndexNotificationRequest extends FormRequest
{
    private const PER_PAGE = 20;

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
            'type' => ['nullable', Rule::in([NewOrderNotification::TYPE, NewContactMessageNotification::TYPE])],
            'status' => ['nullable', Rule::in([NotificationService::FILTER_UNREAD, NotificationService::FILTER_READ])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{type: ?string, status: ?string, per_page: int}
     */
    public function filters(): array
    {
        return [
            'type' => $this->validated('type'),
            'status' => $this->validated('status'),
            'per_page' => self::PER_PAGE,
        ];
    }
}
