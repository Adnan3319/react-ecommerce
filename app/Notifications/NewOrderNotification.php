<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Admin bell entry for a placed order. Only what the dropdown shows is stored: never the phone or the address.
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE = 'order';

    public int $tries = 3;

    public function __construct(private readonly Order $order)
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
            'title' => "New order {$this->order->number}",
            'body' => "{$this->order->shipping_name} · ".Money::format($this->order->total)." · {$this->order->delivery_zone_label}",
            'url' => route('admin.orders.show', $this->order, absolute: false),
            'reference_id' => $this->order->id,
        ];
    }
}
