<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Portfolio extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'client_name',
        'project_date',
        'location',
        'short_description',
        'description',
        'image',
        'gallery',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'gallery' => 'array',
    ];
}