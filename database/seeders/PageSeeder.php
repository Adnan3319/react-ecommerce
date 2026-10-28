<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Static pages linked from the header and footer. Safe to run repeatedly: an existing page is left as the admin edited it.
     */
    public function run(): void
    {
        Page::firstOrCreate(['slug' => 'about-us'], [
            'title' => 'About Us',
            'sub_title' => 'Everyday kitchenware and fashion, made simple',
            'content' => '<p>'.config('app.name').' is an online store for useful kitchen essentials and ladies fashion. We want shopping to be easy: clear prices, honest photos and delivery right to your door.</p>'
                .'<p>Order in a minute, pay with Cash on Delivery and return what does not fit within 7 days.</p>',
            'status' => ActiveStatus::Active,
        ]);
    }
}
