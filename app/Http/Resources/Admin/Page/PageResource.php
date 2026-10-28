<?php

namespace App\Http\Resources\Admin\Page;

use App\Models\Page;
use App\Services\Media\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Page
 */
class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'slug' => $this->slug,
            'content' => $this->content,
            'image_url' => app(FileUploadService::class)->url($this->image),
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
