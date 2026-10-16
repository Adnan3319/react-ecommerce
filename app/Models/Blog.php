<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Blog extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'blogs';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'short_description',
        'description',
        'image',
        'author_name',
        'views_count',
        'sort_order',
        'status',
    ];
}