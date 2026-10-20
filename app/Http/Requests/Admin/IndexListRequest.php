<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of every admin list (search box, status filter, rows per page, page).
 */
class IndexListRequest extends FormRequest
{
    /** Rows-per-page choices of the admin table (first one is the default). Keep in sync with PER_PAGE_OPTIONS in DataTable.tsx. */
    private const PER_PAGE_OPTIONS = [10, 20, 50];

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
        $perPage = $this->integer('per_page', self::PER_PAGE_OPTIONS[0]);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0];
    }
}
