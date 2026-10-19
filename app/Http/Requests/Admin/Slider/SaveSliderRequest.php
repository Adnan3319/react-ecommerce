<?php

namespace App\Http\Requests\Admin\Slider;

use App\Enums\ActiveStatus;
use App\Models\Slider;
use App\Services\Slider\SliderService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by store and update: on update (a bound slider) fields are optional,
 * so partial changes such as a status toggle validate on their own and the image can be kept.
 */
class SaveSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $required = $this->slider() ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:50'],
            // Relative path or http(s) URL only: blocks javascript:/data: links in the hero button.
            'button_link' => ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https?://)\S*$#i'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => [$required, Rule::enum(ActiveStatus::class)],
            // Wide banner: stored as 1920x800 + 960x400 (config/media.php `slider` preset).
            'image' => [
                $required, 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=1920,min_height=800,max_width=5000,max_height=5000',
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->exceedsActiveLimit()) {
                $validator->errors()->add(
                    'status',
                    'You can have at most '.SliderService::MAX_ACTIVE.' active sliders. Deactivate one first.',
                );
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function sliderData(): array
    {
        return $this->safe()->except(['image']);
    }

    /**
     * True when this request would turn a slider active while the limit is already reached.
     * Editing a slider that is active and stays active never counts against the limit.
     */
    private function exceedsActiveLimit(): bool
    {
        $slider = $this->slider();
        $status = $this->has('status') ? $this->enum('status', ActiveStatus::class) : $slider?->status;

        if ($status !== ActiveStatus::Active || $slider?->status === ActiveStatus::Active) {
            return false;
        }

        return Slider::query()->active()->count() >= SliderService::MAX_ACTIVE;
    }

    private function slider(): ?Slider
    {
        $slider = $this->route('slider');

        return $slider instanceof Slider ? $slider : null;
    }
}
