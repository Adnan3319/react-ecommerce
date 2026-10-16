<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'client_name'  => $this->client_name,
            'designation'  => $this->designation,
            'company_name' => $this->company_name,
            'message'      => $this->message,
            'rating'       => $this->rating,
            'image'        => $this->image,
            'sort_order'   => $this->sort_order,
            'status'       => $this->status,
            'created_at'   => $this->created_at?->toDateTimeString(),
            'updated_at'   => $this->updated_at?->toDateTimeString(),
        ];
    }
}