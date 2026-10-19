<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'slug',
        'headline',
        'description',
        'image',
        'sort_order',
        'status',
    ];
}