<?php

namespace App\Http\Requests\Admin\Slider;

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
use App\Http\Requests\Admin\IndexListRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of the admin slider list: the common list params plus the position tab.
 */
class IndexSliderRequest extends IndexListRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'position' => ['nullable', Rule::enum(SliderPosition::class)],
        ];
    }

    /**
     * @return array{search: ?string, status: ?ActiveStatus, per_page: int, position: ?SliderPosition}
     */
    public function filters(): array
    {
        return parent::filters() + [
            'position' => $this->enum('position', SliderPosition::class),
        ];
    }
}
