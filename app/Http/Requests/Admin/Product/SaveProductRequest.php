<?php

namespace App\Http\Requests\Admin\Product;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductPayload;
use App\Services\Product\ProductPayloadChecker;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared by store and update. On update, a request without `has_variants` is a partial update
 * (status / featured toggle of the list): its fields are optional and only they are applied.
 * Money is typed in taka.
 */
class SaveProductRequest extends FormRequest
{
    private const MONEY = ['string', 'regex:/^\d{1,8}(\.\d{1,2})?$/'];

    private const IMAGE = ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=600,min_height=600,max_width=5000,max_height=5000'];

    private const SKU = ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'];

    private const ATTRIBUTES = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'status', 'is_featured', 'meta_title', 'meta_description',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            return;
        }

        $slug = $this->product()?->slug ?? Str::slug((string) $this->input('name'));

        if ($slug !== '') {
            $this->merge(['slug' => $slug]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $required = $this->product() ? 'sometimes' : 'required';
        $matrix = Rule::requiredIf(fn () => $this->hasVariants());
        $simple = Rule::requiredIf(fn () => $this->isFullSave() && ! $this->hasVariants());

        return [
            'category_id' => [$required, 'integer', Rule::exists('categories', 'id')],
            'name' => [$required, 'string', 'max:150'],
            // Soft-deleted rows still hold their slug (DB unique index), so no deleted_at filter here.
            'slug' => [$required, 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($this->product())],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => [$required, Rule::enum(ActiveStatus::class)],
            'is_featured' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],

            'main_image' => ['nullable', 'file', ...self::IMAGE],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', ...self::IMAGE],
            'gallery_keep' => ['nullable', 'array'],
            'gallery_keep.*' => ['integer'],

            'has_variants' => [$this->product() ? 'sometimes' : 'required', 'boolean'],

            'sku' => self::SKU,
            'price' => [$simple, ...self::MONEY],
            'sale_price' => ['nullable', ...self::MONEY],
            'stock' => [$simple, 'integer', 'min:0', 'max:1000000'],

            'heads' => [$matrix, 'nullable', 'array', 'min:1'],
            'heads.*.id' => ['nullable', 'integer'],
            'heads.*.name' => ['required', 'string', 'max:50'],
            'heads.*.values' => ['required', 'array', 'min:1'],
            'heads.*.values.*.id' => ['nullable', 'integer'],
            'heads.*.values.*.value' => ['required', 'string', 'max:50'],

            'variants' => [$matrix, 'nullable', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.values' => ['required', 'array', 'min:1'],
            'variants.*.values.*' => ['required', 'string', 'max:50'],
            'variants.*.sku' => self::SKU,
            'variants.*.price' => ['required', ...self::MONEY],
            'variants.*.sale_price' => ['nullable', ...self::MONEY],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.image_ref' => ['nullable', 'string', 'max:20'],
            'variants.*.status' => ['nullable', Rule::enum(ActiveStatus::class)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $input = $validator->validated();

            if (isset($input['category_id']) && ! Category::query()->active()->leaves()->whereKey($input['category_id'])->exists()) {
                $validator->errors()->add('category_id', 'Choose an active category that has no sub-categories.');
            }

            $errors = app(ProductPayloadChecker::class)->check(
                $input,
                $this->product(),
                $this->file('main_image') instanceof UploadedFile,
                count($this->file('gallery', [])),
            );

            foreach ($errors as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        }];
    }

    public function payload(): ProductPayload
    {
        $input = $this->validated();
        $attributes = Arr::only($input, self::ATTRIBUTES);

        if ($this->has('is_featured')) {
            $attributes['is_featured'] = $this->boolean('is_featured');
        }

        if (! $this->isFullSave()) {
            return new ProductPayload($attributes, fullSave: false);
        }

        return new ProductPayload(
            attributes: $attributes,
            mainImage: $this->file('main_image'),
            gallery: array_values($this->file('gallery', [])),
            galleryKeep: array_map('intval', $input['gallery_keep'] ?? []),
            hasVariants: $this->hasVariants(),
            simple: [
                'sku' => filled($input['sku'] ?? null) ? strtoupper($input['sku']) : null,
                'price' => Money::toMinor($input['price'] ?? 0),
                'sale_price' => Money::toMinor($input['sale_price'] ?? null),
                'stock' => (int) ($input['stock'] ?? 0),
            ],
            heads: $this->headRows($input['heads'] ?? []),
            variants: $this->variantRows($input['variants'] ?? []),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $heads
     * @return list<array{id: ?int, name: string, values: list<array{id: ?int, value: string}>}>
     */
    private function headRows(array $heads): array
    {
        return array_values(array_map(fn (array $head) => [
            'id' => filled($head['id'] ?? null) ? (int) $head['id'] : null,
            'name' => trim($head['name']),
            'values' => array_values(array_map(fn (array $value) => [
                'id' => filled($value['id'] ?? null) ? (int) $value['id'] : null,
                'value' => trim($value['value']),
            ], $head['values'])),
        ], $heads));
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return list<array<string, mixed>>
     */
    private function variantRows(array $variants): array
    {
        return array_values(array_map(fn (array $variant) => [
            'id' => filled($variant['id'] ?? null) ? (int) $variant['id'] : null,
            'values' => array_values(array_map(trim(...), $variant['values'])),
            'sku' => filled($variant['sku'] ?? null) ? strtoupper($variant['sku']) : null,
            'price' => Money::toMinor($variant['price']),
            'sale_price' => Money::toMinor($variant['sale_price'] ?? null),
            'stock' => (int) $variant['stock'],
            'image_ref' => filled($variant['image_ref'] ?? null) ? $variant['image_ref'] : null,
            'status' => $variant['status'] ?? ActiveStatus::Active->value,
        ], $variants));
    }

    private function isFullSave(): bool
    {
        return $this->product() === null || $this->has('has_variants');
    }

    private function hasVariants(): bool
    {
        return $this->boolean('has_variants');
    }

    private function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }
}
