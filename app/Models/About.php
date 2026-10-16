<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class About extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'about';

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'mission',
        'vision',
        'image',
        'experience_years',
        'completed_projects',
        'satisfied_clients',
        'status',
    ];
}