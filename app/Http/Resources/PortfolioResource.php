<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'slug'              => $this->slug,
            'category'          => $this->category,
            'client_name'       => $this->client_name,
            'project_date'      => $this->project_date,
            'location'          => $this->location,
            'short_description' => $this->short_description,
            'description'       => $this->description,
            'image'             => $this->image,
            'gallery'           => $this->gallery,
            'sort_order'        => $this->sort_order,
            'status'            => $this->status,
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}