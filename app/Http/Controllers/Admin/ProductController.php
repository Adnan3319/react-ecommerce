<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\IndexProductRequest;
use App\Http\Requests\Admin\Product\SaveProductRequest;
use App\Http\Resources\Admin\Product\ProductFormResource;
use App\Http\Resources\Admin\Product\ProductListResource;
use App\Models\Product;
use App\Services\Category\CategoryService;
use App\Services\Product\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
    ) {}

    public function index(IndexProductRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Products/Index', [
            'products' => ProductListResource::collection($this->products->paginate($filters)),
            'filters' => [
                ...$filters,
                'status' => $filters['status']?->value,
            ],
            'statuses' => $this->statuses(),
            'categoryTree' => $this->categories->treeForSelect(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', $this->formProps());
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $this->products->create($request->payload());

        Inertia::flash('success', 'Product created.');

        return to_route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'variantHeads.values', 'variants.variantValues']);

        return Inertia::render('Admin/Products/Edit', [
            ...$this->formProps(),
            'product' => ProductFormResource::make($product)->resolve(),
        ]);
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->payload());

        Inertia::flash('success', 'Product updated.');

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        Inertia::flash('success', 'Product deleted.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'categoryTree' => $this->categories->treeForSelect(),
            'galleryMax' => (int) config('media.product_gallery_max'),
            'maxVariantHeads' => (int) config('shop.max_variant_heads'),
            'maxVariants' => (int) config('shop.max_variants_per_product'),
            'statuses' => $this->statuses(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return array_map(
            fn (ActiveStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            ActiveStatus::cases(),
        );
    }
}
