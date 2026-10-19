<?php

namespace App\Services\Category;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Services\Media\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    public const ACTIVE_CACHE_KEY = 'categories.active';

    private const IMAGE_DIRECTORY = 'categories';

    private const IMAGE_PRESET = 'category';

    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * @param  array{search?: ?string, status?: ?ActiveStatus, per_page: int}  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Category::query()
            ->search($filters['search'] ?? null)
            ->withStatus($filters['status'] ?? null)
            ->ordered()
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image): Category
    {
        $path = $image ? $this->uploads->upload($image, self::IMAGE_DIRECTORY, self::IMAGE_PRESET) : null;

        try {
            $category = DB::transaction(fn () => Category::create([...$data, 'image' => $path]));
        } catch (Throwable $e) {
            $this->uploads->delete($path);

            throw $e;
        }

        $this->flushCache();

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data, ?UploadedFile $image, bool $removeImage): Category
    {
        $previousPath = $category->image;
        $newPath = $image ? $this->uploads->upload($image, self::IMAGE_DIRECTORY, self::IMAGE_PRESET) : null;
        $imageChanged = $newPath !== null || $removeImage;

        if ($imageChanged) {
            $data['image'] = $newPath;
        }

        try {
            DB::transaction(fn () => $category->update($data));
        } catch (Throwable $e) {
            $this->uploads->delete($newPath);

            throw $e;
        }

        if ($imageChanged) {
            $this->uploads->delete($previousPath);
        }

        $this->flushCache();

        return $category;
    }

    public function delete(Category $category): void
    {
        $path = $category->image;

        DB::transaction(fn () => $category->delete());

        $this->uploads->delete($path);
        $this->flushCache();
    }

    /**
     * Active categories for the storefront as plain arrays (cached forever, flushed on every write).
     *
     * @return array<int, array<string, ?string>>
     */
    public function activeForStorefront(): array
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => Category::query()
            ->active()
            ->ordered()
            ->get(['name', 'slug', 'headline', 'description', 'image'])
            ->map(fn (Category $category) => [
                'slug' => $category->slug,
                'label' => $category->name,
                'headline' => $category->headline,
                'description' => $category->description,
                'image' => $this->uploads->url($category->image),
            ])
            ->all());
    }

    private function flushCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }
}
