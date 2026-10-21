<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
use App\Models\Slider;
use App\Services\Slider\SliderService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Five hero slides so the Home hero can be reviewed locally. The banners are generated placeholders
 * (a plain gradient without text, the storefront renders the slide text): replace them from the admin. DatabaseSeeder only calls this in the
 * local environment. Safe to run repeatedly (matched by title).
 */
class SliderSampleSeeder extends Seeder
{
    private const WIDTH = 1920;

    private const HEIGHT = 800;

    public function __construct(private readonly SliderService $sliders) {}

    public function run(): void
    {
        foreach ($this->slides() as $order => $slide) {
            if (Slider::withTrashed()->where('title', $slide['title'])->exists()) {
                continue;
            }

            $this->sliders->create([
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'position' => SliderPosition::Hero->value,
                'button_text' => $slide['button'],
                'button_link' => $slide['link'],
                'sort_order' => $order + 1,
                'status' => ActiveStatus::Active->value,
            ], $this->banner($slide['title'], $slide['from'], $slide['to']));
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $from
     * @param  array{0: int, 1: int, 2: int}  $to
     */
    private function banner(string $name, array $from, array $to): UploadedFile
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        for ($x = 0; $x < self::WIDTH; $x++) {
            $t = $x / (self::WIDTH - 1);
            $color = (int) imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $t),
                (int) ($from[1] + ($to[1] - $from[1]) * $t),
                (int) ($from[2] + ($to[2] - $from[2]) * $t),
            );
            imageline($image, $x, 0, $x, self::HEIGHT, $color);
        }

        $path = tempnam(sys_get_temp_dir(), 'sample-slider-').'.png';
        imagepng($image, $path);

        return new UploadedFile($path, Str::slug($name).'.png', 'image/png', null, true);
    }

    /**
     * @return list<array{title: string, subtitle: string, button: string, link: string, from: array{0: int, 1: int, 2: int}, to: array{0: int, 1: int, 2: int}}>
     */
    private function slides(): array
    {
        return [
            ['title' => 'Everyday Kitchen Essentials', 'subtitle' => 'Cookware, storage and glassware for a happier kitchen.', 'button' => 'Shop Kitchenware', 'link' => '/categories/kitchenware', 'from' => [30, 41, 59], 'to' => [217, 119, 6]],
            ['title' => 'New Season Fashion', 'subtitle' => 'Kurtis, panjabis, bags and shoes, fresh this season.', 'button' => 'Shop Fashion', 'link' => '/categories/fashion', 'from' => [131, 24, 67], 'to' => [244, 114, 182]],
            ['title' => 'Up to 20% Off Cookware', 'subtitle' => 'Pressure cookers, fry pans and dinner sets on sale.', 'button' => 'See Offers', 'link' => '/search?q=cook', 'from' => [20, 83, 45], 'to' => [132, 204, 22]],
            ['title' => 'Eid Collection: Panjabi and Saree', 'subtitle' => 'Dress up for the festival with our festive picks.', 'button' => 'Explore Now', 'link' => '/categories/fashion', 'from' => [49, 46, 129], 'to' => [99, 102, 241]],
            ['title' => 'Cash on Delivery All Over Bangladesh', 'subtitle' => 'Order now, pay when it arrives at your door.', 'button' => 'Start Shopping', 'link' => '/search', 'from' => [15, 23, 42], 'to' => [14, 165, 233]],
        ];
    }
}
