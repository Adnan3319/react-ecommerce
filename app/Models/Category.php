<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    private const LIKE_ESCAPE = '!';

    protected $fillable = [
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
    protected function withStatus(Builder $query, ?ActiveStatus $status): void
    {
        $query->when($status, fn (Builder $q) => $q->where('status', $status));
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
     * Name search with LIKE wildcards (% and _) in the term treated literally.
     * "!" is the escape character because "\" is handled differently by MySQL and SQLite.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $escape = self::LIKE_ESCAPE;
        $escaped = str_replace([$escape, '%', '_'], [$escape.$escape, $escape.'%', $escape.'_'], $term);

        $query->whereRaw("name LIKE ? ESCAPE '{$escape}'", ["%{$escaped}%"]);
    }
}
