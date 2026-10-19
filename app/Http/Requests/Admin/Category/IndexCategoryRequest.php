<?php

namespace App\Http\Requests\Admin\Category;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCategoryRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 10;

    private const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ActiveStatus::class)],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{search: ?string, status: ?ActiveStatus, per_page: int}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'status' => $this->enum('status', ActiveStatus::class),
            'per_page' => $this->perPage(),
        ];
    }

    private function perPage(): int
    {
        return min(max($this->integer('per_page', self::DEFAULT_PER_PAGE), self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);
    }
}
