<?php

namespace App\Http\Requests\Admin\Slider;

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
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
    private const MAX_IMAGE_KB = 2048;

    private const MAX_IMAGE_SIDE = 5000;

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
        $position = $this->position();

        return [
            'title' => [$required, 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'position' => [$required, Rule::enum(SliderPosition::class)],
            'button_text' => ['nullable', 'string', 'max:50'],
            // Relative path or http(s) URL only: blocks javascript:/data: links in the slider button.
            'button_link' => ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https?://)\S*$#i'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => [$required, Rule::enum(ActiveStatus::class)],
            // Stored with the preset of the position (config/media.php: `slider` 1920x800, `promo` 1200x800).
            'image' => [
                $required, 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB,
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    $position->minWidth(),
                    $position->minHeight(),
                    self::MAX_IMAGE_SIDE,
                    self::MAX_IMAGE_SIDE,
                ),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->movesWithoutNewImage()) {
                $validator->errors()->add(
                    'image',
                    'Upload a new image: the current one was cropped for the '.$this->slider()->position->label().' position.',
                );
            }

            if ($this->exceedsActiveLimit()) {
                $validator->errors()->add(
                    'status',
                    'You can have at most '.SliderService::MAX_ACTIVE.' active '.$this->position()->label().' sliders. Deactivate one first.',
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
     * The position this request puts the slider in: the submitted one, else the slider's own, else hero.
     */
    public function position(): SliderPosition
    {
        return $this->enum('position', SliderPosition::class) ?? $this->slider()?->position ?? SliderPosition::Hero;
    }

    /**
     * True when an existing slider changes position but keeps its old image, which was cropped for another size.
     */
    private function movesWithoutNewImage(): bool
    {
        $slider = $this->slider();

        return $slider !== null && $this->position() !== $slider->position && ! $this->hasFile('image');
    }

    /**
     * True when this request would make a slider active in a position that already has the maximum.
     * A slider that is active and stays active in the same position never counts against the limit.
     */
    private function exceedsActiveLimit(): bool
    {
        $slider = $this->slider();
        $status = $this->has('status') ? $this->enum('status', ActiveStatus::class) : $slider?->status;
        $position = $this->position();

        if ($status !== ActiveStatus::Active) {
            return false;
        }

        if ($slider?->status === ActiveStatus::Active && $slider->position === $position) {
            return false;
        }

        return Slider::query()->active()->inPosition($position)->count() >= SliderService::MAX_ACTIVE;
    }

    private function slider(): ?Slider
    {
        $slider = $this->route('slider');

        return $slider instanceof Slider ? $slider : null;
    }
}
