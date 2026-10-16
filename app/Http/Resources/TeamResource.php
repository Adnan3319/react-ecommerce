<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'designation' => $this->designation,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'image'       => $this->image,
            'facebook'    => $this->facebook,
            'twitter'     => $this->twitter,
            'linkedin'    => $this->linkedin,
            'sort_order'  => $this->sort_order,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toDateTimeString(),
            'updated_at'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}