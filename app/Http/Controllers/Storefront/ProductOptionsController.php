<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Product\ProductPageService;
use Illuminate\Http\JsonResponse;

class ProductOptionsController extends Controller
{
    public function __construct(private readonly ProductPageService $pages) {}

    public function show(string $slug): JsonResponse
    {
        $productId = $this->pages->activeIdBySlug($slug);

        abort_if($productId === null, 404);

        return ApiResponse::success($this->pages->options($productId));
    }
}
