<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\TestimonialSource;
use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasListFilters;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Real customer feedback shown on the storefront, entered by an admin (never generated).
 */
class Testimonial extends Model
{
    use HasAuditFields, HasFactory, HasListFilters, SoftDeletes;

    public const SEARCH_COLUMN = 'customer_name';

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public const MAX_TEXT_LENGTH = 500;

    protected $fillable = [
        'customer_name',
        'text',
        'rating',
        'source',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'source' => TestimonialSource::class,
            'status' => ActiveStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Testimonial>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }

    /**
     * @param  Builder<Testimonial>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
