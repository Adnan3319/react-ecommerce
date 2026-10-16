<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Contact extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'is_read',
        'status',
    ];
}