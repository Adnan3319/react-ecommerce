<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasListFilters;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasAuditFields, HasFactory, HasListFilters, SoftDeletes;

    public const SEARCH_COLUMN = 'name';

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
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
