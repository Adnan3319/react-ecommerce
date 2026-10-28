<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            CompanySeeder::class,
            PageSeeder::class,
        ]);

        // Fake feedback, sample sliders and sample products only on a developer machine, never on a live store.
        if (app()->environment('local')) {
            $this->call([TestimonialSampleSeeder::class, SliderSampleSeeder::class, ProductSampleSeeder::class]);
        }
    }
}
