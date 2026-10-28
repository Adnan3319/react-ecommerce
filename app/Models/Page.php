<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'slug',
        'content',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    /**
     * @param  Builder<Page>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }
}
