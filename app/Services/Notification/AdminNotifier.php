<?php

namespace App\Services\Notification;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use App\Notifications\NewOrderNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Tells every active admin about a new order or contact message (one queued database notification each).
 * A failure here is reported and swallowed: it must never break order placement or the contact form.
 */
class AdminNotifier
{
    public function newOrder(Order $order): void
    {
        $this->send(new NewOrderNotification($order));
    }

    public function newContactMessage(ContactMessage $message): void
    {
        $this->send(new NewContactMessageNotification($message));
    }

    private function send(Notification $notification): void
    {
        try {
            $admins = User::query()->active()->get(['id']);

            NotificationFacade::send($admins, $notification);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
