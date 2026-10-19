<?php

namespace App\Services\Slider;

use App\Models\Slider;
use App\Services\Media\FileUploadService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SliderService
{
    public const ACTIVE_CACHE_KEY = 'sliders.active';

    public const MAX_ACTIVE = 5;

    public const IMAGE_PRESET = 'slider';

    private const IMAGE_DIRECTORY = 'sliders';

    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * @return Collection<int, Slider>
     */
    public function all(): Collection
    {
        return Slider::query()->ordered()->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $image): Slider
    {
        $path = $this->uploads->upload($image, self::IMAGE_DIRECTORY, self::IMAGE_PRESET);

        try {
            $slider = DB::transaction(fn () => Slider::create([...$data, 'image' => $path]));
        } catch (Throwable $e) {
            $this->uploads->delete($path);

            throw $e;
        }

        $this->flushCache();

        return $slider;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Slider $slider, array $data, ?UploadedFile $image): Slider
    {
        $previousPath = $slider->image;
        $newPath = $image ? $this->uploads->upload($image, self::IMAGE_DIRECTORY, self::IMAGE_PRESET) : null;

        if ($newPath !== null) {
            $data['image'] = $newPath;
        }

        try {
            DB::transaction(fn () => $slider->update($data));
        } catch (Throwable $e) {
            $this->uploads->delete($newPath);

            throw $e;
        }

        if ($newPath !== null) {
            $this->uploads->delete($previousPath);
        }

        $this->flushCache();

        return $slider;
    }

    public function delete(Slider $slider): void
    {
        $path = $slider->image;

        DB::transaction(fn () => $slider->delete());

        $this->uploads->delete($path);
        $this->flushCache();
    }

    /**
     * Active sliders for the storefront hero as plain arrays (cached forever, flushed on every write).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForStorefront(): array
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => Slider::query()
            ->active()
            ->ordered()
            ->limit(self::MAX_ACTIVE)
            ->get(['id', 'title', 'subtitle', 'image', 'button_text', 'button_link'])
            ->map(fn (Slider $slider) => [
                'id' => $slider->id,
                'title' => $slider->title,
                'subtitle' => $slider->subtitle,
                'image' => $this->uploads->url($slider->image),
                'image_srcset' => $this->uploads->srcset($slider->image, self::IMAGE_PRESET),
                'button_text' => $slider->button_text,
                'button_link' => $slider->button_link,
            ])
            ->all());
    }

    private function flushCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }
}
