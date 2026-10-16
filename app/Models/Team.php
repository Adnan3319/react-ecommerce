<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Team extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'designation',
        'email',
        'phone',
        'image',
        'facebook',
        'twitter',
        'linkedin',
        'sort_order',
        'status',
    ];
}