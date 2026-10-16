<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'clients';

    protected $fillable = [
        'client_name',
        'designation',
        'company_name',
        'message',
        'rating',
        'image',
        'sort_order',
        'status',
    ];
}