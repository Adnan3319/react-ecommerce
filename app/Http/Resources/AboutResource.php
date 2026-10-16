<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'subtitle'           => $this->subtitle,
            'description'        => $this->description,
            'mission'            => $this->mission,
            'vision'             => $this->vision,
            'image'              => $this->image,
            'experience_years'   => $this->experience_years,
            'completed_projects' => $this->completed_projects,
            'satisfied_clients'  => $this->satisfied_clients,
            'status'             => $this->status,
            'created_at'         => $this->created_at?->toDateTimeString(),
            'updated_at'         => $this->updated_at?->toDateTimeString(),
        ];
    }
}