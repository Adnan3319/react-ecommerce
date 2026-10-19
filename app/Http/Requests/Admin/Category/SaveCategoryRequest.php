<?php

namespace App\Http\Requests\Admin\Category;

use App\Enums\ActiveStatus;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared by store and update: on update (a bound category) fields are optional,
 * so partial changes such as a status toggle validate on their own.
 */
class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            return;
        }

        $slug = $this->category()?->slug ?? Str::slug((string) $this->input('name'));

        if ($slug !== '') {
            $this->merge(['slug' => $slug]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $required = $this->category() ? 'sometimes' : 'required';

        return [
            'name' => [
                $required, 'string', 'max:255',
                Rule::unique('categories', 'name')->ignore($this->category())->whereNull('deleted_at'),
            ],
            // Soft-deleted rows still hold their slug (DB unique index), so no deleted_at filter here.
            'slug' => [
                $required, 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($this->category()),
            ],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => [$required, Rule::enum(ActiveStatus::class)],
            // Square logo: stored as 300x300 (config/media.php `category` preset).
            'image' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:512',
                'dimensions:min_width=300,min_height=300,max_width=1000,max_height=1000',
            ],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryData(): array
    {
        return $this->safe()->except(['image', 'remove_image']);
    }

    private function category(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }
}
