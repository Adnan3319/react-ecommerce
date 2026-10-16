<?php

namespace App\Http\Controllers\Api;

use App\Models\Slider;
use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $sliders = Slider::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('title', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            SliderResource::collection($sliders),
            'Sliders fetched successfully'
        );
    }

    public function store(StoreSliderRequest $request): JsonResponse
    {
        $slider = Slider::create($request->validated());

        return ApiResponse::success(
            new SliderResource($slider),
            'Slider created successfully',
            201
        );
    }

    public function show(Slider $slider): JsonResponse
    {
        return ApiResponse::success(
            new SliderResource($slider),
            'Slider fetched successfully'
        );
    }

    public function update(UpdateSliderRequest $request, Slider $slider): JsonResponse
    {
        $slider->update($request->validated());

        return ApiResponse::success(
            new SliderResource($slider),
            'Slider updated successfully'
        );
    }

    public function destroy(Slider $slider): JsonResponse
    {
        $slider->delete();

        return ApiResponse::success(
            null,
            'Slider deleted successfully'
        );
    }
}