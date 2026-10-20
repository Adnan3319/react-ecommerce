<?php

namespace App\Http\Resources\Admin\Slider;

use App\Models\Slider;
use App\Services\Media\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Slider
 */
class SliderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uploads = app(FileUploadService::class);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image_url' => $uploads->url($this->image),
            'image_srcset' => $uploads->srcset($this->image, $this->position->preset()),
            'position' => $this->position->value,
            'position_label' => $this->position->label(),
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'sort_order' => $this->sort_order,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
