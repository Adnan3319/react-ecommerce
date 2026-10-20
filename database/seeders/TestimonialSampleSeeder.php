<?php

namespace Database\Seeders;

use App\Enums\TestimonialSource;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Three clearly fake testimonials so the storefront section can be designed locally. DatabaseSeeder only
 * calls this in the local environment: invented feedback must never reach a live store.
 */
class TestimonialSampleSeeder extends Seeder
{
    public const NAME_PREFIX = '[Sample] ';

    public function run(): void
    {
        foreach ($this->samples() as $order => $sample) {
            Testimonial::query()->updateOrCreate(
                ['customer_name' => self::NAME_PREFIX.$sample['name']],
                [
                    'text' => $sample['text'],
                    'rating' => $sample['rating'],
                    'source' => $sample['source'],
                    'sort_order' => $order + 1,
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, text: string, rating: int, source: TestimonialSource}>
     */
    private function samples(): array
    {
        return [
            ['name' => 'Customer One', 'text' => 'Sample feedback for layout testing only.', 'rating' => 5, 'source' => TestimonialSource::Facebook],
            ['name' => 'Customer Two', 'text' => 'Sample feedback for layout testing only.', 'rating' => 4, 'source' => TestimonialSource::Messenger],
            ['name' => 'Customer Three', 'text' => 'Sample feedback for layout testing only.', 'rating' => 5, 'source' => TestimonialSource::Whatsapp],
        ];
    }
}
