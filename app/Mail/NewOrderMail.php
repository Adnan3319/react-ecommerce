<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "New order" mail of the admin. Sent from NotifyAdminOfNewOrder, which is already queued, so the mail
 * itself is not. The delivery address and the customer's note stay in the admin panel.
 */
class NewOrderMail extends Mailable
{
    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New order {$this->order->number}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.new',
            with: [
                'order' => $this->order,
                'items' => $this->order->items->map(fn ($item): array => [
                    'name' => $item->variant_label ? "{$item->product_name} ({$item->variant_label})" : $item->product_name,
                    'quantity' => $item->quantity,
                    'total' => Money::format($item->line_total),
                ]),
                'deliveryCharge' => Money::format($this->order->delivery_charge),
                'total' => Money::format($this->order->total),
                'url' => route('admin.orders.show', $this->order),
            ],
        );
    }
}
