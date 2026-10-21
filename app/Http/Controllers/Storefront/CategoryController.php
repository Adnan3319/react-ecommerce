<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\ListingProps;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\ListingSeo;
use App\Services\Storefront\ProductFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ListingSeo $seo,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $category = $this->catalog->activeCategory($slug);

        abort_if($category === null, 404);

        $filter = ProductFilter::fromQuery($request->query())->forCategoryPage();
        $page = $this->catalog->listing($category, $filter);

        return Inertia::render('Categories/Show', ListingProps::from(
            $category,
            $page,
            $this->seo->forCategory($category, $filter, $page['products']->currentPage()),
        ));
    }
}
