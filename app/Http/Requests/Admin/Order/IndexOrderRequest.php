<?php

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of the admin order list (status tab, search box, date range, source, rows per page, page).
 */
class IndexOrderRequest extends FormRequest
{
    /** Date range presets → days back from today (today included). */
    public const RANGES = ['today' => 1, '7d' => 7, '30d' => 30];

    /** Keep in sync with PER_PAGE_OPTIONS in DataTable.tsx. */
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
            'order_status' => ['nullable', Rule::enum(OrderStatus::class)],
            'source' => ['nullable', Rule::enum(OrderSource::class)],
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{search: ?string, status: ?OrderStatus, source: ?OrderSource, range: ?string, from: ?CarbonInterface, per_page: int}
     */
    public function filters(): array
    {
        $range = $this->validated('range');

        return [
            'search' => $this->validated('search'),
            'status' => $this->enum('order_status', OrderStatus::class),
            'source' => $this->enum('source', OrderSource::class),
            'range' => $range,
            'from' => $range === null ? null : now()->subDays(self::RANGES[$range] - 1)->startOfDay(),
            'per_page' => $this->perPage(),
        ];
    }

    private function perPage(): int
    {
        $perPage = $this->integer('per_page', self::PER_PAGE_OPTIONS[0]);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0];
    }
}
