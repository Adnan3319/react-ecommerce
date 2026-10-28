<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
        'city',
        'latitude',
        'longitude',
        'website',
        'working_hours',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'about_text',
        'delivery_charge_inside',
        'delivery_charge_outside',
    ];

    protected function casts(): array
    {
        return [
            'delivery_charge_inside' => 'integer',
            'delivery_charge_outside' => 'integer',
        ];
    }
}
