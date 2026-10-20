<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasListFilters;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasAuditFields, HasFactory, HasListFilters, SoftDeletes;

    public const SEARCH_COLUMN = 'name';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'headline',
        'description',
        'image',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /**
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }

    /**
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Top-level categories.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Categories without sub-categories (sub-categories and childless top-level ones).
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function leaves(Builder $query): void
    {
        $query->doesntHave('children');
    }

    /**
     * Each top-level category immediately followed by its sub-categories. Correlated
     * subqueries (not a join) keep the unqualified search/status scopes unambiguous.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function treeOrdered(Builder $query): void
    {
        $table = $query->getModel()->getTable();
        $parent = fn (string $column) => "(SELECT p.{$column} FROM {$table} p WHERE p.id = {$table}.parent_id)";

        $query
            ->orderByRaw("COALESCE({$parent('sort_order')}, {$table}.sort_order)")
            ->orderByRaw("COALESCE({$parent('name')}, {$table}.name)")
            ->orderByRaw("COALESCE({$table}.parent_id, {$table}.id)")
            ->orderByRaw("{$table}.parent_id IS NOT NULL")
            ->orderBy("{$table}.sort_order")
            ->orderBy("{$table}.name");
    }
}
