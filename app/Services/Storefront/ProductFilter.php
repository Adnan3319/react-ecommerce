<?php

namespace App\Services\Storefront;

use App\Support\Money;
use InvalidArgumentException;

/**
 * The listing query string (`?q=&category=&min=&max=&in_stock=1&sort=&f[size]=m,l`) as a validated
 * value. Anything invalid is ignored, never an error. Prices come in taka and are kept in minor units.
 * Head and value keys are lower-cased names, so "Size" and "size" are one filter.
 */
final readonly class ProductFilter
{
    public const SORT_NEWEST = 'newest';

    public const SORT_PRICE_ASC = 'price_asc';

    public const SORT_PRICE_DESC = 'price_desc';

    public const SORT_BEST_MATCH = 'best_match';

    public const SORTS = [self::SORT_NEWEST, self::SORT_PRICE_ASC, self::SORT_PRICE_DESC, self::SORT_BEST_MATCH];

    public const MIN_TERM_LENGTH = 2;

    private const MAX_TERM_LENGTH = 100;

    private const MAX_KEY_LENGTH = 50;

    private const MAX_HEADS = 5;

    private const MAX_VALUES_PER_HEAD = 20;

    private const MAX_PRICE_MINOR = 1_000_000_000;

    /**
     * @param  array<string, list<string>>  $heads  head key => value keys
     */
    public function __construct(
        public ?string $q = null,
        public ?string $category = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public bool $inStock = false,
        public string $sort = self::SORT_NEWEST,
        public array $heads = [],
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $min = self::price($query['min'] ?? null);
        $max = self::price($query['max'] ?? null);

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $q = self::term($query['q'] ?? null);

        return new self(
            q: $q,
            category: self::key($query['category'] ?? null),
            minPrice: $min,
            maxPrice: $max,
            inStock: filter_var($query['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            sort: self::sort($query['sort'] ?? null, $q),
            heads: self::heads($query['f'] ?? null),
        );
    }

    /**
     * The same filter on a category page: the category comes from the route and there is no search term.
     */
    public function forCategoryPage(): self
    {
        return new self(null, null, $this->minPrice, $this->maxPrice, $this->inStock, self::sort($this->sort, null), $this->heads);
    }

    /**
     * The term the products are matched against; null when there is none or it is too short.
     */
    public function searchTerm(): ?string
    {
        return $this->q !== null && mb_strlen($this->q) >= self::MIN_TERM_LENGTH ? $this->q : null;
    }

    /**
     * True when a term was typed but is too short to search with.
     */
    public function hasShortTerm(): bool
    {
        return $this->q !== null && $this->searchTerm() === null;
    }

    /**
     * Keeps only the heads and values the category really has.
     *
     * @param  array<string, list<string>>  $known  head key => value keys
     */
    public function restrictedTo(array $known): self
    {
        $heads = [];

        foreach ($this->heads as $head => $values) {
            $kept = array_values(array_intersect($values, $known[$head] ?? []));

            if ($kept !== []) {
                $heads[$head] = $kept;
            }
        }

        return new self($this->q, $this->category, $this->minPrice, $this->maxPrice, $this->inStock, $this->sort, $heads);
    }

    /**
     * True when price, stock or an option value narrows the variants a product must have.
     */
    public function hasVariantConditions(): bool
    {
        return $this->minPrice !== null || $this->maxPrice !== null || $this->inStock || $this->heads !== [];
    }

    /**
     * True when a filter or a non-default sort is active (the page is then not indexable).
     */
    public function isNarrowed(): bool
    {
        return $this->hasVariantConditions() || $this->sort !== self::sort(null, $this->q);
    }

    /**
     * @return array{q: ?string, q_too_short: bool, category: ?string, min: ?string, max: ?string, in_stock: bool, sort: string, f: array<string, list<string>>}
     */
    public function toArray(): array
    {
        return [
            'q' => $this->q,
            'q_too_short' => $this->hasShortTerm(),
            'category' => $this->category,
            'min' => $this->minPrice === null ? null : Money::toTaka($this->minPrice),
            'max' => $this->maxPrice === null ? null : Money::toTaka($this->maxPrice),
            'in_stock' => $this->inStock,
            'sort' => $this->sort,
            'f' => $this->heads,
        ];
    }

    /**
     * Best match is the default while a usable term is set, otherwise Newest; it means nothing without one.
     */
    private static function sort(mixed $requested, ?string $q): string
    {
        $default = $q !== null && mb_strlen($q) >= self::MIN_TERM_LENGTH ? self::SORT_BEST_MATCH : self::SORT_NEWEST;

        if (! is_string($requested) || ! in_array($requested, self::SORTS, true)) {
            return $default;
        }

        return $requested === self::SORT_BEST_MATCH ? $default : $requested;
    }

    private static function term(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $term = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $value)), 0, self::MAX_TERM_LENGTH);

        return $term === '' ? null : $term;
    }

    private static function key(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $key = mb_strtolower(trim($value));

        return $key === '' || mb_strlen($key) > self::MAX_KEY_LENGTH ? null : $key;
    }

    private static function price(mixed $value): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        try {
            $minor = Money::toMinor($value);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $minor !== null && $minor <= self::MAX_PRICE_MINOR ? $minor : null;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function heads(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $heads = [];

        foreach ($value as $head => $raw) {
            $headKey = self::key((string) $head);
            $values = self::values($raw);

            if ($headKey !== null && $values !== []) {
                $heads[$headKey] = $values;
            }

            if (count($heads) === self::MAX_HEADS) {
                break;
            }
        }

        return $heads;
    }

    /**
     * @return list<string>
     */
    private static function values(mixed $raw): array
    {
        $parts = is_array($raw) ? $raw : (is_string($raw) ? explode(',', $raw) : []);
        $values = [];

        foreach ($parts as $part) {
            $key = self::key($part);

            if ($key !== null) {
                $values[$key] = $key;
            }
        }

        return array_slice(array_values($values), 0, self::MAX_VALUES_PER_HEAD);
    }
}
