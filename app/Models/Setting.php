<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Setting extends Model
{
    use HasFactory, HasAuditFields;

    protected $table = 'settings';

    protected $fillable = [
        'group_name',
        'key',
        'value',
    ];
}