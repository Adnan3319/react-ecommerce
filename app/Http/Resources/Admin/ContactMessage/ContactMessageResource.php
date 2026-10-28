<?php

namespace App\Http\Resources\Admin\ContactMessage;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContactMessage
 */
class ContactMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
