<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

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
