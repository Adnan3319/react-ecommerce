<?php

namespace App\Services\Slider;

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
use App\Models\Slider;
use App\Services\Media\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SliderService
{
    /** Active sliders allowed per position. */
    public const MAX_ACTIVE = 5;

    private const ACTIVE_CACHE_PREFIX = 'sliders.active.';

    private const IMAGE_DIRECTORY = 'sliders';

    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * Cache key of the active sliders of a position (one key per position).
     */
    public static function cacheKey(SliderPosition $position): string
    {
        return self::ACTIVE_CACHE_PREFIX.$position->value;
    }

    /**
     * @param  array{search?: ?string, status?: ?ActiveStatus, per_page: int, position?: ?SliderPosition}  $filters
     * @return LengthAwarePaginator<int, Slider>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Slider::query()
            ->search($filters['search'] ?? null)
            ->withStatus($filters['status'] ?? null)
            ->inPosition($filters['position'] ?? null)
            ->ordered()
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $image): Slider
    {
        $position = SliderPosition::from($data['position']);
        $path = $this->uploads->upload($image, self::IMAGE_DIRECTORY, $position->preset());

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
     * A new image is cropped for the (possibly new) position of the slider.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Slider $slider, array $data, ?UploadedFile $image): Slider
    {
        $previousPath = $slider->image;
        $position = isset($data['position']) ? SliderPosition::from($data['position']) : $slider->position;
        $newPath = $image ? $this->uploads->upload($image, self::IMAGE_DIRECTORY, $position->preset()) : null;

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
     * Active sliders for the storefront as plain arrays: up to 5 hero slides in order, and the first
     * active promo slider (or null). Each position is cached under its own key and every write clears both.
     *
     * @return array{hero: array<int, array<string, mixed>>, promo: ?array<string, mixed>}
     */
    public function activeForStorefront(): array
    {
        return [
            'hero' => $this->activeIn(SliderPosition::Hero),
            'promo' => $this->activeIn(SliderPosition::Promo, 1)[0] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeIn(SliderPosition $position, int $limit = self::MAX_ACTIVE): array
    {
        return Cache::rememberForever(self::cacheKey($position), fn () => Slider::query()
            ->active()
            ->inPosition($position)
            ->ordered()
            ->limit($limit)
            ->get(['id', 'title', 'subtitle', 'image', 'button_text', 'button_link'])
            ->map(fn (Slider $slider) => [
                'id' => $slider->id,
                'title' => $slider->title,
                'subtitle' => $slider->subtitle,
                'image' => $this->uploads->url($slider->image),
                'image_srcset' => $this->uploads->srcset($slider->image, $position->preset()),
                'button_text' => $slider->button_text,
                'button_link' => $slider->button_link,
            ])
            ->all());
    }

    private function flushCache(): void
    {
        foreach (SliderPosition::cases() as $position) {
            Cache::forget(self::cacheKey($position));
        }
    }
}
