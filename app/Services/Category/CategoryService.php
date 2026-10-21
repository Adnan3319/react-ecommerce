<?php

namespace App\Services\Category;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Services\Media\FileUploadService;
use App\Services\Product\ProductCache;
use App\Services\Storefront\CatalogFilterCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CategoryService
{
    public const ACTIVE_CACHE_KEY = 'categories.active';

    private const IMAGE_DIRECTORY = 'categories';

    private const IMAGE_PRESET = 'category';

    public function __construct(
        private readonly FileUploadService $uploads,
        private readonly CatalogFilterCache $catalogFilters,
    ) {}

    /**
     * @param  array{search?: ?string, status?: ?ActiveStatus, per_page: int}  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Category::query()
            ->search($filters['search'] ?? null)
            ->withStatus($filters['status'] ?? null)
            ->treeOrdered()
            ->with('parent:id,name')
            ->withCount('children')
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

    /**
     * Active top-level categories a category can be filed under.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function parentOptions(): array
    {
        return Category::query()
            ->active()
            ->roots()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
            ->all();
    }

    /**
     * Active top-level categories with their active sub-categories, for pickers (product form, filters).
     *
     * @return array<int, array{id: int, name: string, children: array<int, array{id: int, name: string}>}>
     */
    public function treeForSelect(): array
    {
        return Category::query()
            ->active()
            ->roots()
            ->ordered()
            ->with(['children' => fn ($query) => $query->active()])
            ->get()
            ->map(fn (Category $root) => [
                'id' => $root->id,
                'name' => $root->name,
                'children' => $root->children->map(fn (Category $child) => ['id' => $child->id, 'name' => $child->name])->all(),
            ])
            ->all();
    }

    /**
     * @throws ValidationException when the category still has sub-categories
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Delete or move its sub-categories first.',
            ]);
        }

        $path = $category->image;

        DB::transaction(fn () => $category->delete());

        $this->uploads->delete($path);
        $this->flushCache();
    }

    /**
     * Active top-level categories with their active sub-categories nested under `children`,
     * as plain arrays (cached forever, flushed on every write).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForStorefront(): array
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => Category::query()
            ->active()
            ->roots()
            ->ordered()
            ->with(['children' => fn ($query) => $query->active()])
            ->get()
            ->map(fn (Category $root) => [
                ...$this->storefrontNode($root),
                'children' => $root->children->map(fn (Category $child) => $this->storefrontNode($child))->all(),
            ])
            ->all());
    }

    /**
     * @return array<string, ?string>
     */
    private function storefrontNode(Category $category): array
    {
        return [
            'slug' => $category->slug,
            'label' => $category->name,
            'headline' => $category->headline,
            'description' => $category->description,
            'image' => $this->uploads->url($category->image),
        ];
    }

    private function flushCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
        // The Home rails list products per active category.
        Cache::forget(ProductCache::CATEGORY_RAILS_KEY);
        // A category's visibility or tree position changes which products the listing filters cover.
        $this->catalogFilters->flush();
    }
}
