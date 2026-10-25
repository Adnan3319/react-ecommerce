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
        'website',
        'working_hours',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'about_text',
        'latitude',
        'longitude',
        'about_heading',
        'about_subheading',
        'about_button_text',
        'about_button_link',
        'about_bg_image',
    ];
}