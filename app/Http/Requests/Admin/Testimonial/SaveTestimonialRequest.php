<?php

namespace App\Http\Requests\Admin\Testimonial;

use App\Enums\ActiveStatus;
use App\Enums\TestimonialSource;
use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by store and update: on update (a bound testimonial) fields are optional,
 * so partial changes such as a status toggle validate on their own.
 */
class SaveTestimonialRequest extends FormRequest
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
        $required = $this->route('testimonial') instanceof Testimonial ? 'sometimes' : 'required';

        return [
            'customer_name' => [$required, 'string', 'min:2', 'max:80'],
            'text' => [$required, 'string', 'max:'.Testimonial::MAX_TEXT_LENGTH],
            'rating' => [$required, 'integer', 'between:'.Testimonial::MIN_RATING.','.Testimonial::MAX_RATING],
            'source' => [$required, Rule::enum(TestimonialSource::class)],
            'status' => [$required, Rule::enum(ActiveStatus::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function testimonialData(): array
    {
        return $this->validated();
    }
}
