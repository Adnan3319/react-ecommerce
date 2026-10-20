<?php

namespace App\Http\Requests\Admin\Product;

use App\Enums\ActiveStatus;
use App\Http\Requests\Admin\IndexListRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends IndexListRequest
{
    /** Stock states the list can be filtered by. */
    public const STOCK_STATES = ['in', 'low', 'out'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'featured' => ['nullable', 'boolean'],
            'stock' => ['nullable', Rule::in(self::STOCK_STATES)],
        ];
    }

    /**
     * @return array{search: ?string, status: ?ActiveStatus, per_page: int, category_id: ?int, featured: ?bool, stock: ?string}
     */
    public function filters(): array
    {
        return parent::filters() + [
            'category_id' => $this->filled('category_id') ? $this->integer('category_id') : null,
            'featured' => $this->filled('featured') ? $this->boolean('featured') : null,
            'stock' => $this->validated('stock'),
        ];
    }
}
