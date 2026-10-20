<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasListFilters;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use HasAuditFields, HasFactory, HasListFilters, SoftDeletes;

    public const SEARCH_COLUMN = 'title';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'position',
        'button_text',
        'button_link',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'position' => SliderPosition::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Slider>  $query
     */
    #[Scope]
    protected function inPosition(Builder $query, ?SliderPosition $position): void
    {
        $query->when($position, fn (Builder $q) => $q->where('position', $position));
    }

    /**
     * @param  Builder<Slider>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }

    /**
     * @param  Builder<Slider>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
