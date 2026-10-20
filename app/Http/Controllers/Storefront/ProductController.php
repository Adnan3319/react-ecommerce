<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Order\DeliveryZones;
use App\Services\Product\ProductPageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PERMANENT_REDIRECT = 301;

    public function __construct(
        private readonly ProductPageService $pages,
        private readonly DeliveryZones $zones,
    ) {}

    public function show(string $slug): Response|RedirectResponse
    {
        $productId = $this->pages->activeIdBySlug($slug);

        if ($productId === null) {
            $current = $this->pages->redirectTarget($slug);

            abort_if($current === null, 404);

            return redirect()->route('products.show', $current, self::PERMANENT_REDIRECT);
        }

        return Inertia::render('Products/Show', [
            ...$this->pages->page($productId),
            'deliveryZones' => $this->deliveryZones(),
            'maxQty' => (int) config('shop.max_qty_per_item'),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, charge: int}>
     */
    private function deliveryZones(): array
    {
        return array_map(
            fn (string $key) => ['key' => $key, ...$this->zones->get($key)],
            $this->zones->keys(),
        );
    }
}
