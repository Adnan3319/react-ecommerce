<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Slider extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'button_text',
        'button_link',
        'sort_order',
        'status',
    ];
}