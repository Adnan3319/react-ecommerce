<?php

namespace App\Enums;

/**
 * Where the customer's feedback came from (shown as a small tag next to it).
 */
enum TestimonialSource: string
{
    case Facebook = 'facebook';
    case Messenger = 'messenger';
    case Whatsapp = 'whatsapp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            default => ucfirst($this->value),
        };
    }
}
