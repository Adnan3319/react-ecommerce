<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SuggestSearchRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Storefront\ProductFilter;
use App\Services\Storefront\SearchService;
use Illuminate\Http\JsonResponse;

class SearchSuggestController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function __invoke(SuggestSearchRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->search->suggest(ProductFilter::fromQuery($request->validated())),
        );
    }
}
