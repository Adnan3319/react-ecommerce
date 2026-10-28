<?php

namespace App\Services\Contact;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Services\Notification\AdminNotifier;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public const FILTER_UNREAD = 'unread';

    public const FILTER_READ = 'read';

    public function __construct(
        private readonly UnreadMessageCount $unreadCount,
        private readonly AdminNotifier $notifier,
    ) {}

    /**
     * @param  array{mobile: string, name: string, email?: ?string, message: string}  $data
     */
    public function create(array $data): ContactMessage
    {
        $message = ContactMessage::query()->create($data);

        $this->unreadCount->forget();
        $this->notifier->newContactMessage($message);

        return $message;
    }

    /**
     * Unread first, then newest first.
     *
     * @param  array{search: ?string, read: ?string, per_page: int}  $filters
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->search($filters['search'])
            ->when($filters['read'] === self::FILTER_UNREAD, fn ($query) => $query->where('is_read', false))
            ->when($filters['read'] === self::FILTER_READ, fn ($query) => $query->where('is_read', true))
            ->orderBy('is_read')
            ->latest()
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    public function markRead(ContactMessage $message): void
    {
        if ($message->is_read) {
            return;
        }

        $message->update(['is_read' => true]);
        $this->unreadCount->forget();
    }

    public function delete(ContactMessage $message): void
    {
        $message->delete();
        $this->unreadCount->forget();
    }

    /**
     * How many orders were placed with this (normalized) mobile number.
     */
    public function ordersCount(ContactMessage $message): int
    {
        return Order::query()->where('shipping_phone', $message->mobile)->count();
    }
}
