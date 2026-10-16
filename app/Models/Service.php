<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Service extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'image',
        'icon',
        'sort_order',
        'status',
    ];
}