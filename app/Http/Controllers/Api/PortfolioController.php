<?php

namespace App\Http\Controllers\Api;

use App\Models\Portfolio;
use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioResource;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $portfolios = Portfolio::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('title', 'like', '%' . request('search') . '%')
            )
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            PortfolioResource::collection($portfolios),
            'Portfolios fetched successfully'
        );
    }

    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $portfolio = Portfolio::create($data);

        return ApiResponse::success(
            new PortfolioResource($portfolio),
            'Portfolio created successfully',
            201
        );
    }

    public function show(Portfolio $portfolio): JsonResponse
    {
        return ApiResponse::success(
            new PortfolioResource($portfolio),
            'Portfolio fetched successfully'
        );
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $portfolio->update($data);

        return ApiResponse::success(
            new PortfolioResource($portfolio),
            'Portfolio updated successfully'
        );
    }

    public function destroy(Portfolio $portfolio): JsonResponse
    {
        $portfolio->delete();

        return ApiResponse::success(
            null,
            'Portfolio deleted successfully'
        );
    }
}