<?php

namespace App\Services\Product;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Models\VariantValue;
use App\Services\Media\FileUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductService
{
    private const IMAGE_DIRECTORY = 'products';

    private const IMAGE_PRESET = 'product';

    private const FEATURED_LIMIT = 8;

    /** Existing heads wait here while names/positions are reassigned, so swaps do not hit the unique indexes. */
    private const PARKED_HEAD_POSITION = 100;

    public function __construct(
        private readonly FileUploadService $uploads,
        private readonly StockService $stock,
        private readonly SkuGenerator $skus,
        private readonly ProductCache $cache,
    ) {}

    /**
     * @param  array{search?: ?string, status?: ?ActiveStatus, per_page: int, category_id?: ?int, featured?: ?bool, stock?: ?string}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Product::query()
            ->searchNameOrSku($filters['search'] ?? null)
            ->withStatus($filters['status'] ?? null)
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->whereIn('category_id', $this->categoryWithChildren($id)))
            ->when(($filters['featured'] ?? null) !== null, fn (Builder $query) => $query->where('is_featured', $filters['featured']))
            ->when($filters['stock'] ?? null, fn (Builder $query, string $state) => $query->stockState($state))
            ->with([
                'category:id,name,parent_id',
                'category.parent:id,name',
                'mainImage:id,product_id,path',
                'activeVariants:id,product_id,price,sale_price,stock,status',
            ])
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    public function create(ProductPayload $payload): Product
    {
        return $this->save(new Product, $payload);
    }

    public function update(Product $product, ProductPayload $payload): Product
    {
        return $this->save($product, $payload);
    }

    /**
     * Soft deletes the product and its variants (images stay on disk).
     */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            // Guard for orders goes here once orders exist.
            foreach ($product->variants as $variant) {
                $this->retire($variant);
            }

            $product->delete();
        });

        $this->cache->flush();
    }

    /**
     * Featured products for the Home page as plain arrays (cached forever, flushed on every product or stock write).
     * The price shown is the cheapest effective price among the in-stock variants.
     *
     * @return array<int, array<string, mixed>>
     */
    public function featuredForStorefront(): array
    {
        return Cache::rememberForever(ProductCache::FEATURED_KEY, fn () => Product::query()
            ->active()
            ->featured()
            ->inStock()
            ->with(['mainImage', 'activeVariants'])
            ->latest('id')
            ->limit(self::FEATURED_LIMIT)
            ->get()
            ->map(fn (Product $product) => $this->featuredCard($product))
            ->all());
    }

    /**
     * Uploads first, then one transaction; new files are removed if it fails and replaced or
     * dropped files are removed only after it committed.
     */
    private function save(Product $product, ProductPayload $payload): Product
    {
        $uploaded = $payload->fullSave ? $this->storeUploads($payload) : ['main' => null, 'gallery' => []];

        try {
            [$saved, $obsolete] = DB::transaction(fn () => $this->persist($product, $payload, $uploaded));
        } catch (Throwable $e) {
            $this->deleteFiles([$uploaded['main'], ...$uploaded['gallery']]);

            throw $e;
        }

        $this->deleteFiles($obsolete);
        $this->cache->flush();

        return $saved;
    }

    /**
     * @param  array{main: ?string, gallery: array<int, string>}  $uploaded
     * @return array{0: Product, 1: array<int, string>} the saved product and the file paths that are no longer used
     */
    private function persist(Product $product, ProductPayload $payload, array $uploaded): array
    {
        $product->fill($payload->attributes)->save();

        if (! $payload->fullSave) {
            return [$product, []];
        }

        ['refs' => $imageRefs, 'obsolete' => $obsolete] = $this->syncImages($product, $payload, $uploaded);

        $this->syncVariants($product, $payload, $imageRefs);

        if (! $product->images()->where('is_main', true)->exists()) {
            throw ValidationException::withMessages(['main_image' => 'A main image is required.']);
        }

        return [$product->refresh(), $obsolete];
    }

    /**
     * @return array{main: ?string, gallery: array<int, string>}
     */
    private function storeUploads(ProductPayload $payload): array
    {
        $stored = ['main' => null, 'gallery' => []];

        try {
            if ($payload->mainImage instanceof UploadedFile) {
                $stored['main'] = $this->uploads->upload($payload->mainImage, self::IMAGE_DIRECTORY, self::IMAGE_PRESET);
            }

            foreach ($payload->gallery as $file) {
                $stored['gallery'][] = $this->uploads->upload($file, self::IMAGE_DIRECTORY, self::IMAGE_PRESET);
            }
        } catch (Throwable $e) {
            $this->deleteFiles([$stored['main'], ...$stored['gallery']]);

            throw $e;
        }

        return $stored;
    }

    /**
     * Main image: a new upload replaces the file of the existing row in place (variants keep pointing at it).
     * Gallery: kept images are reordered, the others are dropped, new uploads follow.
     *
     * @param  array{main: ?string, gallery: array<int, string>}  $uploaded
     * @return array{refs: array<string, int>, obsolete: array<int, string>}
     */
    private function syncImages(Product $product, ProductPayload $payload, array $uploaded): array
    {
        $refs = [];
        $obsolete = [];

        if ($uploaded['main'] !== null) {
            $main = $product->images()->where('is_main', true)->first();

            if ($main) {
                $obsolete[] = $main->path;
                $main->update(['path' => $uploaded['main']]);
            } else {
                $main = $product->images()->create(['path' => $uploaded['main'], 'is_main' => true, 'sort_order' => 0]);
            }

            $refs['new:main'] = $main->id;
        }

        $gallery = $product->images()->where('is_main', false)->get()->keyBy('id');
        $order = 0;

        foreach ($payload->galleryKeep as $id) {
            $gallery->pull($id)?->update(['sort_order' => $order++]);
        }

        foreach ($gallery as $dropped) {
            $obsolete[] = $dropped->path;
            $dropped->delete();
        }

        foreach ($uploaded['gallery'] as $index => $path) {
            $image = $product->images()->create(['path' => $path, 'is_main' => false, 'sort_order' => $order++]);
            $refs["new:{$index}"] = $image->id;
        }

        return ['refs' => $refs, 'obsolete' => $obsolete];
    }

    /**
     * @param  array<string, int>  $imageRefs
     */
    private function syncVariants(Product $product, ProductPayload $payload, array $imageRefs): void
    {
        $live = $product->variants()->get()->keyBy('id');

        if ($payload->hasVariants) {
            $this->syncMatrix($product, $payload, $live, $imageRefs);
        } else {
            $this->syncSimple($product, $payload, $live);
        }
    }

    /**
     * A simple product: heads and values go, exactly one default variant stays.
     *
     * @param  Collection<int, ProductVariant>  $live
     */
    private function syncSimple(Product $product, ProductPayload $payload, Collection $live): void
    {
        $default = $live->first(fn (ProductVariant $variant) => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION);

        foreach ($live as $variant) {
            if ($variant !== $default) {
                $this->retire($variant);
            }
        }

        VariantHead::query()->where('product_id', $product->id)->delete();

        $simple = $payload->simple;
        $variant = $default ?? new ProductVariant(['product_id' => $product->id]);
        $variant->fill([
            'sku' => $simple['sku'] ?? $variant->sku ?? $this->skus->generate($product),
            'price' => $simple['price'],
            'sale_price' => $simple['sale_price'],
            'status' => ActiveStatus::Active,
            'sort_order' => 0,
        ])->save();

        $this->writeStock($variant, $simple['stock'], $default === null);
    }

    /**
     * Heads, values and variants submitted by id/text. Order matters: retire what is gone, park
     * changing keys, write heads → values → variants, then delete the heads and values nobody uses.
     *
     * @param  Collection<int, ProductVariant>  $live
     * @param  array<string, int>  $imageRefs
     */
    private function syncMatrix(Product $product, ProductPayload $payload, Collection $live, array $imageRefs): void
    {
        $submittedIds = collect($payload->variants)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach ($live->all() as $variant) {
            $isDefault = $variant->combination_key === ProductVariant::DEFAULT_COMBINATION;

            if ($isDefault || ! in_array($variant->id, $submittedIds, true)) {
                $this->retire($variant);
                $live->forget($variant->id);
            }
        }

        // Kept variants may swap combinations: park them on unique keys first.
        foreach ($live as $variant) {
            $variant->forceFill(['combination_key' => 'tmp-'.$variant->id])->save();
        }

        ['values' => $valueMap, 'headIds' => $headIds, 'valueIds' => $valueIds] = $this->syncHeadsAndValues($product, $payload);

        $reserved = array_values(array_filter(array_map(fn (array $variant) => $variant['sku'] ?? null, $payload->variants)));

        foreach ($payload->variants as $position => $row) {
            $variant = ($row['id'] ? $live->get((int) $row['id']) : null) ?? new ProductVariant(['product_id' => $product->id]);
            $isNew = ! $variant->exists;

            if ($isNew) {
                $variant->forceFill(['combination_key' => "tmp-new-{$position}"]);
            }

            $sku = $row['sku'] ?? ($isNew ? $this->skus->generate($product, $row['values'], $reserved) : $variant->sku);
            $reserved[] = $sku;

            $variant->fill([
                'sku' => $sku,
                'price' => $row['price'],
                'sale_price' => $row['sale_price'],
                'status' => $row['status'],
                'sort_order' => $position,
                'product_image_id' => $this->resolveImageRef($row['image_ref'], $imageRefs),
            ])->save();

            $variant->syncVariantValues(array_map(
                fn (int $head, string $text) => $valueMap[$head][$this->normalize($text)]->id,
                array_keys($row['values']),
                $row['values'],
            ));

            $this->writeStock($variant, $row['stock'], $isNew);
        }

        VariantValue::query()->whereIn('variant_head_id', $headIds)->whereNotIn('id', $valueIds)->delete();
        VariantHead::query()->where('product_id', $product->id)->whereNotIn('id', $headIds)->delete();
    }

    /**
     * Creates and updates heads and the values that some variant uses. Existing rows are matched by id and
     * parked on temporary names first, so renames and reorders that swap names cannot collide.
     *
     * @return array{values: array<int, array<string, VariantValue>>, headIds: array<int, int>, valueIds: array<int, int>}
     */
    private function syncHeadsAndValues(Product $product, ProductPayload $payload): array
    {
        $existing = $product->variantHeads()->with('values')->get()->keyBy('id');
        $used = $this->usedValueTexts($payload);

        foreach ($payload->heads as $i => $row) {
            $head = $row['id'] ? $existing->get((int) $row['id']) : null;

            if ($head === null) {
                continue;
            }

            $head->forceFill(['name' => '~'.$head->id, 'position' => self::PARKED_HEAD_POSITION + $i])->save();

            foreach ($head->values as $value) {
                $value->forceFill(['value' => '~'.$value->id])->save();
            }
        }

        $valueMap = [];
        $headIds = [];
        $valueIds = [];

        foreach ($payload->heads as $i => $row) {
            $head = ($row['id'] ? $existing->get((int) $row['id']) : null) ?? new VariantHead(['product_id' => $product->id]);
            $existingValues = $head->exists ? $head->values->keyBy('id') : collect();

            $head->fill(['name' => $row['name'], 'position' => $i])->save();
            $headIds[] = $head->id;

            $position = 0;

            foreach ($row['values'] as $valueRow) {
                $key = $this->normalize($valueRow['value']);

                if (! isset($used[$i][$key])) {
                    continue;
                }

                $value = ($valueRow['id'] ? $existingValues->get((int) $valueRow['id']) : null)
                    ?? new VariantValue(['variant_head_id' => $head->id]);
                $value->fill(['value' => $valueRow['value'], 'position' => $position++])->save();

                $valueMap[$i][$key] = $value;
                $valueIds[] = $value->id;
            }
        }

        return ['values' => $valueMap, 'headIds' => $headIds, 'valueIds' => $valueIds];
    }

    /**
     * Value texts (normalized) that at least one submitted variant selects, per head index.
     *
     * @return array<int, array<string, true>>
     */
    private function usedValueTexts(ProductPayload $payload): array
    {
        $used = [];

        foreach ($payload->variants as $row) {
            foreach ($row['values'] as $head => $text) {
                $used[$head][$this->normalize($text)] = true;
            }
        }

        return $used;
    }

    /**
     * Soft deletes a variant and drops its selections, so its values can be deleted later.
     * The variant keeps its stock and movement history.
     */
    private function retire(ProductVariant $variant): void
    {
        $variant->variantValues()->detach();
        $variant->delete();
    }

    private function writeStock(ProductVariant $variant, int $quantity, bool $isNew): void
    {
        if ($isNew) {
            $this->stock->setInitial($variant, $quantity);

            return;
        }

        $this->stock->adjust($variant, $quantity);
    }

    /**
     * @param  array<string, int>  $imageRefs
     */
    private function resolveImageRef(?string $ref, array $imageRefs): ?int
    {
        if ($ref === null || $ref === '') {
            return null;
        }

        return ctype_digit($ref) ? (int) $ref : ($imageRefs[$ref] ?? null);
    }

    /**
     * @return array<int, int>
     */
    private function categoryWithChildren(int $categoryId): array
    {
        return Category::query()
            ->where('id', $categoryId)
            ->orWhere('parent_id', $categoryId)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function featuredCard(Product $product): array
    {
        $variant = $product->activeVariants
            ->filter(fn (ProductVariant $candidate) => $candidate->stock > 0)
            ->sortBy('effective_price')
            ->first();
        $path = $product->mainImage?->path;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'thumb' => $this->uploads->variantUrl($path, 'thumb'),
            'srcset' => $this->uploads->srcset($path, self::IMAGE_PRESET),
            'price' => $variant?->price,
            'sale_price' => $variant?->sale_price,
            'discount_percent' => $variant?->discount_percent,
        ];
    }

    /**
     * @param  array<int, ?string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->uploads->delete($path);
        }
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim($text));
    }
}
