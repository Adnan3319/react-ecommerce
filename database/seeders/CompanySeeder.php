<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Default company info for the storefront and admin. Safe to run repeatedly (matched by id).
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Checkit',
                'email' => 'support@checkit.com',
                'phone' => '+8801700000000',
                'address' => 'Dhaka, Bangladesh',
            ]
        );
    }
}