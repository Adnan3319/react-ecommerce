<?php

namespace Database\Seeders;

use App\Enums\TestimonialSource;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Three invented testimonials so the storefront section can be designed locally. DatabaseSeeder only
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
            ['name' => 'Rahim Uddin', 'text' => 'Ordered a pressure cooker on Friday and it reached Dhaka in two days. Packaging was neat and I paid cash on delivery.', 'rating' => 5, 'source' => TestimonialSource::Facebook],
            ['name' => 'Nusrat Jahan', 'text' => 'The cotton kurti fits perfectly and the colour is exactly like the photo. They confirmed my order on Messenger within minutes.', 'rating' => 5, 'source' => TestimonialSource::Messenger],
            ['name' => 'Tanvir Ahmed', 'text' => 'Good quality glass storage set for the price. Delivery to Chattogram took three days, a little slow but well packed.', 'rating' => 4, 'source' => TestimonialSource::Whatsapp],
        ];
    }
}
