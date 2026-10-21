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

class SearchController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ListingSeo $seo,
    ) {}

    public function index(Request $request): Response
    {
        $filter = ProductFilter::fromQuery($request->query());
        $category = $filter->category === null ? null : $this->catalog->activeCategory($filter->category);

        return Inertia::render('Search', ListingProps::from(
            $category,
            $this->catalog->listing($category, $filter),
            $this->seo->forSearch($filter),
        ));
    }
}
