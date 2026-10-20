<?php

namespace App\Models\Concerns;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scopes behind the admin list toolbar. The model sets `public const SEARCH_COLUMN`.
 */
trait HasListFilters
{
    private const LIKE_ESCAPE = '!';

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, ?ActiveStatus $status): void
    {
        $query->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    /**
     * Search with LIKE wildcards (% and _) in the term treated literally.
     * "!" is the escape character because "\" is handled differently by MySQL and SQLite.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $escape = self::LIKE_ESCAPE;
        $escaped = str_replace([$escape, '%', '_'], [$escape.$escape, $escape.'%', $escape.'_'], $term);

        $query->whereRaw(static::SEARCH_COLUMN." LIKE ? ESCAPE '{$escape}'", ["%{$escaped}%"]);
    }
}
