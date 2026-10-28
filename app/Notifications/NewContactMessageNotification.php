<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Admin bell entry for a contact message. Only a short excerpt is stored, never the phone number.
 */
class NewContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE = 'message';

    private const BODY_LIMIT = 80;

    public int $tries = 3;

    public function __construct(private readonly ContactMessage $message)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{type: string, title: string, body: string, url: string, reference_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => "New message from {$this->message->name}",
            'body' => Str::limit($this->message->message, self::BODY_LIMIT),
            'url' => route('admin.messages.show', $this->message, absolute: false),
            'reference_id' => $this->message->id,
        ];
    }
}
