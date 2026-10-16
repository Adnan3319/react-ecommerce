<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'slug'              => $this->slug,
            'category'          => $this->category,
            'short_description' => $this->short_description,
            'description'       => $this->description,
            'image'             => $this->image,
            'author_name'       => $this->author_name,
            'views_count'       => $this->views_count,
            'sort_order'        => $this->sort_order,
            'status'            => $this->status,
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}